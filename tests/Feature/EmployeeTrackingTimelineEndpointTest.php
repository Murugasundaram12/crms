<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeTrackingTimelineEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed for the configured in-memory feature test database.');
        }

        parent::setUp();
    }

    public function test_timeline_endpoint_returns_validated_segmented_route_payload(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-21',
            'check_in_at' => Carbon::parse('2026-07-21 10:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 1, 11.000000, 77.000000, '2026-07-21 10:00:00', 'walking');
        $this->point($employee->id, $attendance->id, 2, 11.000100, 77.000000, '2026-07-21 10:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 3, 11.000100, 77.000000, '2026-07-21 10:01:30', 'walking');
        $this->point($employee->id, $attendance->id, 4, 11.000200, 77.000000, '2026-07-21 10:02:00', 'still', speed: 0);
        $this->point($employee->id, $attendance->id, 5, 11.002000, 77.000000, '2026-07-21 10:02:05', 'walking');
        $this->point($employee->id, $attendance->id, 6, 11.000300, 77.000000, '2026-07-21 10:20:00', 'walking');
        $this->point($employee->id, $attendance->id, 7, 11.000400, 77.000000, '2026-07-21 10:21:00', 'walking');

        $response = $this
            ->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-21',
                'gps_debug' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('employeeId', $employee->id)
            ->assertJsonPath('gpsDebug.raw_point_count', 7)
            ->assertJsonPath('gpsDebug.segment_count', 2);

        $payload = $response->json();

        $this->assertSame(2, count($payload['polylineSegments']));
        $this->assertSame(2, count($payload['directionsSegments']));
        $this->assertSame(4, count($payload['polylinePoints']));
        $this->assertSame($payload['polylinePoints'], collect($payload['polylineSegments'])->pluck('points')->flatten(1)->values()->all());
        $this->assertSame($payload['polylinePoints'][0]['id'], $payload['directionsSegments'][0]['origin']['id']);
        $this->assertSame($payload['polylineSegments'][0]['points'][1]['id'], $payload['directionsSegments'][0]['destination']['id']);
        $this->assertContains('duplicate_location', array_keys($payload['gpsDebug']['rejection_reason_count']));
        $this->assertContains('speed_exceeded', array_keys($payload['gpsDebug']['rejection_reason_count']));
        $this->assertNotContains('still', collect($payload['timeLineItems'])->whereIn('type', ['vehicle', 'walk'])->pluck('type')->all());
    }

    public function test_travelling_tracking_type_draws_route_even_when_activity_reports_still(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 08:44:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 11, 9.863315, 78.0211683, '2026-07-22 08:44:02', 'still', type: 'travelling');
        $this->point($employee->id, $attendance->id, 12, 9.864495, 78.0223417, '2026-07-22 08:44:15', 'still', type: 'travelling');
        $this->point($employee->id, $attendance->id, 13, 9.8692767, 78.0247083, '2026-07-22 08:45:38', 'still', type: 'travelling');

        $response = $this
            ->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ]);

        $response->assertOk();

        $payload = $response->json();

        $this->assertGreaterThanOrEqual(1, count($payload['polylineSegments']));
        $this->assertGreaterThanOrEqual(2, count($payload['polylinePoints']));
        $this->assertSame(['vehicle'], collect($payload['timeLineItems'])->pluck('type')->unique()->values()->all());
    }

    public function test_no_attendance_returns_empty_timeline_even_if_stray_rows_exist(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $otherAttendance = Attendance::query()->create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $otherAttendance->id, 21, 11.000000, 77.000000, '2026-07-22 09:01:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertSame([], $payload['attendanceIds']);
        $this->assertSame([], $payload['timeLineItems']);
        $this->assertSame([], $payload['polylineSegments']);
        $this->assertSame(0, $payload['gpsDistanceKm']);
    }

    public function test_one_attendance_returns_only_its_tracking_rows(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);
        $otherAttendance = Attendance::query()->create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 31, 11.000000, 77.000000, '2026-07-22 09:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 32, 11.000100, 77.000000, '2026-07-22 09:02:00', 'walking');
        $this->point($employee->id, $otherAttendance->id, 33, 12.000000, 78.000000, '2026-07-22 09:03:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertSame([$attendance->id], $payload['attendanceIds']);
        $this->assertSame([31, 32], collect($payload['timeLineItems'])->pluck('id')->all());
    }

    public function test_multiple_attendance_sessions_remain_separate_and_do_not_connect(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $firstAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-22 10:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);
        $secondAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 12:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $firstAttendance->id, 41, 11.000000, 77.000000, '2026-07-22 09:01:00', 'walking');
        $this->point($employee->id, $firstAttendance->id, 42, 11.000100, 77.000000, '2026-07-22 09:02:00', 'walking');
        $this->point($employee->id, $secondAttendance->id, 43, 11.010000, 77.000000, '2026-07-22 12:01:00', 'walking');
        $this->point($employee->id, $secondAttendance->id, 44, 11.010100, 77.000000, '2026-07-22 12:02:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertSame([$firstAttendance->id, $secondAttendance->id], $payload['attendanceIds']);
        $this->assertSame([$firstAttendance->id, $secondAttendance->id], collect($payload['polylineSegments'])->pluck('attendance_id')->all());
        $this->assertSame([$firstAttendance->id, $secondAttendance->id], collect($payload['directionsSegments'])->pluck('attendance_id')->all());
    }

    public function test_still_closes_travel_block_and_later_movement_starts_new_block(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 51, 11.000000, 77.000000, '2026-07-22 09:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 52, 11.000100, 77.000000, '2026-07-22 09:02:00', 'walking');
        $this->point($employee->id, $attendance->id, 53, 11.000110, 77.000000, '2026-07-22 09:03:00', 'still', speed: 0, type: 'still');
        $this->point($employee->id, $attendance->id, 54, 11.001000, 77.000000, '2026-07-22 09:10:00', 'walking');
        $this->point($employee->id, $attendance->id, 55, 11.001100, 77.000000, '2026-07-22 09:11:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertCount(2, $payload['routeBlocks']);
        $this->assertSame([[51, 52], [54, 55]], collect($payload['routeBlocks'])->map(fn (array $block) => $block['source_point_ids'])->all());
    }

    public function test_check_in_check_out_and_still_are_markers_only(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-22 10:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 61, 11.000000, 77.000000, '2026-07-22 09:00:00', 'still', type: 'checked_in');
        $this->point($employee->id, $attendance->id, 62, 11.000100, 77.000000, '2026-07-22 09:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 63, 11.000200, 77.000000, '2026-07-22 09:02:00', 'walking');
        $this->point($employee->id, $attendance->id, 64, 11.000210, 77.000000, '2026-07-22 09:03:00', 'still', speed: 0, type: 'still');
        $this->point($employee->id, $attendance->id, 65, 11.000220, 77.000000, '2026-07-22 10:00:00', 'still', speed: 0, type: 'checked_out');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertSame(['checkIn', 'walk', 'walk', 'still', 'checkOut'], collect($payload['timeLineItems'])->pluck('type')->all());
        $this->assertSame([62, 63], $payload['routeBlocks'][0]['source_point_ids']);
        $this->assertSame([62, 63], $payload['directionsSegments'][0]['source_point_ids']);
    }

    public function test_actual_gps_and_road_route_response_data_remain_separate(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 71, 11.000000, 77.000000, '2026-07-22 09:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 72, 11.000100, 77.000000, '2026-07-22 09:02:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-22',
            ])
            ->assertOk()
            ->json();

        $this->assertGreaterThan(0, $payload['gpsDistanceKm']);
        $this->assertNull($payload['directionsDistanceKm']);
        $this->assertNotEmpty($payload['polylineSegments']);
        $this->assertNotEmpty($payload['directionsSegments']);
    }

    public function test_timeline_requires_existing_authorization(): void
    {
        $employee = User::factory()->create(['status' => 'active']);

        $this->post(route('dashboard.getTimeLineAjax'), [
            'userId' => $employee->id,
            'date' => '2026-07-22',
        ])->assertRedirect();
    }

    private function point(
        int $employeeId,
        int $attendanceId,
        int $id,
        float $latitude,
        float $longitude,
        string $recordedAt,
        string $activity,
        float $accuracy = 8,
        float $speed = 1,
        string $type = 'location'
    ): void {
        LocationTracking::query()->create([
            'id' => $id,
            'attendance_id' => $attendanceId,
            'employee_id' => $employeeId,
            'device_id' => 'test-device',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'activity' => $activity,
            'is_gps_on' => true,
            'is_mock_location' => false,
            'battery_percentage' => 80,
            'type' => $type,
            'recorded_at' => Carbon::parse($recordedAt, 'Asia/Kolkata'),
        ]);
    }
}
