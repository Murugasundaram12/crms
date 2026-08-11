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
            ->assertJsonPath('trackingHealth.raw_saved_points_count', 7)
            ->assertJsonPath('trackingHealth.accepted_points_count', 4)
            ->assertJsonPath('trackingHealth.rejected_points_count', 3)
            ->assertJsonPath('gpsDebug.segment_count', 2);

        $payload = $response->json();

        $this->assertSame(2, count($payload['polylineSegments']));
        $this->assertSame(2, $payload['trackingHealth']['backend_segment_count']);
        $this->assertSame(2, $payload['trackingHealth']['gap_count']);
        $this->assertSame(1080, $payload['trackingHealth']['longest_gap_seconds']);
        $this->assertSame(2, count($payload['directionsSegments']));
        $this->assertSame(4, count($payload['polylinePoints']));
        $this->assertSame($payload['polylinePoints'], collect($payload['polylineSegments'])->pluck('points')->flatten(1)->values()->all());
        $this->assertSame($payload['polylinePoints'][0]['id'], $payload['directionsSegments'][0]['origin']['id']);
        $this->assertSame($payload['polylineSegments'][0]['points'][1]['id'], $payload['directionsSegments'][0]['destination']['id']);
        $this->assertContains('duplicate_location', array_keys($payload['gpsDebug']['rejection_reason_count']));
        $this->assertContains('speed_exceeded', array_keys($payload['gpsDebug']['rejection_reason_count']));
        $this->assertNotContains('still', collect($payload['timeLineItems'])->whereIn('type', ['vehicle', 'walk'])->pluck('type')->all());
    }

    public function test_low_coverage_still_returns_available_route_segments_without_connecting_gap(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-30',
            'check_in_at' => Carbon::parse('2026-07-30 07:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-30 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 101, 11.000000, 77.000000, '2026-07-30 07:01:00', 'walking');
        $this->point($employee->id, $attendance->id, 102, 11.000400, 77.000000, '2026-07-30 07:02:00', 'walking');
        $this->point($employee->id, $attendance->id, 103, 11.010000, 77.000000, '2026-07-30 08:10:00', 'walking');
        $this->point($employee->id, $attendance->id, 104, 11.010400, 77.000000, '2026-07-30 08:11:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-30',
            ])
            ->assertOk()
            ->json();

        $this->assertLessThan(5, $payload['trackingHealth']['tracking_coverage_percentage']);
        $this->assertSame(4, $payload['trackingHealth']['raw_saved_points_count']);
        $this->assertSame(2, $payload['trackingHealth']['backend_segment_count']);
        $this->assertSame([[101, 102], [103, 104]], collect($payload['polylineSegments'])->map(fn (array $segment) => collect($segment['points'])->pluck('id')->all())->all());
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

    public function test_open_attendance_does_not_derive_checkout_from_last_tracking_row(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-07-31 12:05:23', 'Asia/Kolkata'));

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 09:05:23', 'Asia/Kolkata'),
            'check_out_at' => null,
            'status' => 'present',
            'notes' => 'Lat: 9.8920194, Long: 78.0762717. Address: Thirupparankundram Salai, Pasumalai, Madurai, Tamil Nadu, 625005, India. Time: 09:05 AM. Device: UKQ1.231108.001',
        ]);

        $this->point($employee->id, $attendance->id, 81, 9.8935533, 78.0782983, '2026-07-31 09:06:05', 'walking', type: 'travelling');
        $this->point($employee->id, $attendance->id, 82, 9.9147980, 78.0979712, '2026-07-31 09:26:02', 'walking', type: 'travelling');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('03:00:00', $payload['totalAttendanceTime']);
        $this->assertSame('active', $payload['attendances'][0]['status']);
        $this->assertTrue($payload['attendances'][0]['is_open']);
        $this->assertNull($payload['attendances'][0]['check_out_at']);
        $this->assertNull($payload['attendances'][0]['check_out_time']);
        $this->assertSame(['checkIn', 'walk', 'walk'], collect($payload['timeLineItems'])->pluck('type')->all());
        $this->assertFalse(collect($payload['timelineEvents'])->contains(fn (array $event): bool => $event['type'] === 'checkOut'));
        $this->assertSame('attendance_notes', $payload['timeLineItems'][0]['source']);
        $this->assertSame(9.8920194, $payload['timeLineItems'][0]['latitude']);
        $this->assertSame(78.0762717, $payload['timeLineItems'][0]['longitude']);
        $this->assertSame('2026-07-31 09:26:02', $payload['trackingHealth']['last_tracking_at']);
        $this->assertSame('09:05 AM', $payload['timeLineItems'][0]['startTime']);

        Carbon::setTestNow();
    }

    public function test_selected_date_open_attendance_is_not_merged_with_previous_day_checkout_overlap(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-07-31 10:05:23', 'Asia/Kolkata'));

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $previousAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-30',
            'check_in_at' => Carbon::parse('2026-07-31 01:44:50', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 07:14:50', 'Asia/Kolkata'),
            'worked_minutes' => 1438,
            'status' => 'present',
        ]);
        $currentAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 09:05:23', 'Asia/Kolkata'),
            'check_out_at' => null,
            'status' => 'present',
            'notes' => 'Lat: 9.8920194, Long: 78.0762717. Address: Thirupparankundram Salai, Pasumalai, Madurai, Tamil Nadu, 625005, India. Time: 09:05 AM. Device: UKQ1.231108.001',
        ]);

        $this->point($employee->id, $previousAttendance->id, 121, 11.000000, 77.000000, '2026-07-31 07:10:00', 'walking');
        $this->point($employee->id, $currentAttendance->id, 122, 9.8935533, 78.0782983, '2026-07-31 09:06:05', 'walking', type: 'travelling');
        $this->point($employee->id, $currentAttendance->id, 123, 9.9147980, 78.0979712, '2026-07-31 09:26:02', 'walking', type: 'travelling');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame([$currentAttendance->id], $payload['attendanceIds']);
        $this->assertSame($currentAttendance->id, $payload['attendanceId']);
        $this->assertSame('09:05 AM', $payload['attendances'][0]['check_in_time']);
        $this->assertNull($payload['attendances'][0]['check_out_time']);
        $this->assertSame('active', $payload['attendances'][0]['status']);
        $this->assertSame('Active', $payload['attendances'][0]['status_label']);
        $this->assertSame('01:00:00', $payload['totalAttendanceTime']);
        $this->assertFalse(collect($payload['timeLineItems'])->contains(fn (array $item): bool => $item['attendanceId'] === $previousAttendance->id));
        $this->assertFalse(collect($payload['timeLineItems'])->contains(fn (array $item): bool => $item['type'] === 'checkOut'));
        $this->assertSame('attendance_notes', $payload['timeLineItems'][0]['source']);

        Carbon::setTestNow();
    }

    public function test_completed_attendance_uses_actual_checkout_timestamp(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-07-31 18:00:00', 'Asia/Kolkata'));

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 09:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 10:30:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 91, 11.000000, 77.000000, '2026-07-31 09:10:00', 'walking');
        $this->point($employee->id, $attendance->id, 92, 11.000500, 77.000000, '2026-07-31 09:20:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('01:30:00', $payload['totalAttendanceTime']);
        $this->assertFalse($payload['attendances'][0]['is_open']);
        $this->assertSame('10:30 AM', $payload['attendances'][0]['check_out_time']);
        $this->assertSame(['checkIn', 'walk', 'walk', 'checkOut'], collect($payload['timeLineItems'])->pluck('type')->all());
        $this->assertTrue(collect($payload['timelineEvents'])->contains(fn (array $event): bool => $event['type'] === 'checkOut'
            && $event['start_time'] === '10:30 AM'));

        Carbon::setTestNow();
    }

    public function test_overnight_attendance_uses_real_checkout_without_date_end_fallback(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-22',
            'check_in_at' => Carbon::parse('2026-07-22 21:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-23 07:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 101, 11.000000, 77.000000, '2026-07-23 06:10:00', 'walking');
        $this->point($employee->id, $attendance->id, 102, 11.000500, 77.000000, '2026-07-23 06:20:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-23',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('10:00:00', $payload['totalAttendanceTime']);
        $this->assertSame('07:00 AM', $payload['attendances'][0]['check_out_time']);
        $this->assertSame('checkOut', collect($payload['timeLineItems'])->last()['type']);
    }

    public function test_multiple_attendance_sessions_use_each_session_checkout_state(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-07-31 13:00:00', 'Asia/Kolkata'));

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $firstAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 08:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);
        $secondAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 10:00:00', 'Asia/Kolkata'),
            'check_out_at' => null,
            'status' => 'present',
        ]);

        $this->point($employee->id, $firstAttendance->id, 111, 11.000000, 77.000000, '2026-07-31 08:10:00', 'walking');
        $this->point($employee->id, $firstAttendance->id, 112, 11.000500, 77.000000, '2026-07-31 08:20:00', 'walking');
        $this->point($employee->id, $secondAttendance->id, 113, 11.010000, 77.000000, '2026-07-31 10:10:00', 'walking');
        $this->point($employee->id, $secondAttendance->id, 114, 11.010500, 77.000000, '2026-07-31 10:20:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('04:00:00', $payload['totalAttendanceTime']);
        $this->assertSame([$firstAttendance->id, $secondAttendance->id], $payload['attendanceIds']);
        $this->assertSame([false, true], collect($payload['attendances'])->pluck('is_open')->all());
        $this->assertSame(1, collect($payload['timeLineItems'])->where('type', 'checkOut')->count());
        $this->assertSame($firstAttendance->id, collect($payload['timeLineItems'])->firstWhere('type', 'checkOut')['attendanceId']);

        Carbon::setTestNow();
    }

    public function test_mobile_admin_timeline_uses_same_attendance_selection_as_web_timeline(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $previousAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-30',
            'check_in_at' => Carbon::parse('2026-07-30 22:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 07:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);
        $selectedDateAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $previousAttendance->id, 131, 11.000000, 77.000000, '2026-07-31 06:50:00', 'walking');
        $this->point($employee->id, $selectedDateAttendance->id, 132, 11.010000, 77.000000, '2026-07-31 09:10:00', 'walking');

        $webPayload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $mobilePayload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->getJson('/api/admin/employees/' . $employee->id . '/timeline?date=2026-07-31')
            ->assertOk()
            ->json();

        $this->assertSame([$selectedDateAttendance->id], $webPayload['attendanceIds']);
        $this->assertSame($webPayload['attendanceIds'], $mobilePayload['attendance_ids']);
        $this->assertFalse(collect($mobilePayload['trackings'])->contains(fn (array $item): bool => ($item['attendanceId'] ?? null) === $previousAttendance->id));
    }

    public function test_timeline_tracked_time_exposes_active_duration_and_span_duration(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-07-31 11:00:00', 'Asia/Kolkata'));

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 09:00:00', 'Asia/Kolkata'),
            'check_out_at' => null,
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 141, 11.000000, 77.000000, '2026-07-31 09:00:00', 'walking');
        $this->point($employee->id, $attendance->id, 142, 11.000500, 77.000000, '2026-07-31 09:05:00', 'walking');
        $this->point($employee->id, $attendance->id, 143, 11.001000, 77.000000, '2026-07-31 09:15:00', 'walking');
        $this->point($employee->id, $attendance->id, 144, 11.001500, 77.000000, '2026-07-31 09:20:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('00:10:00', $payload['totalTrackedTime']);
        $this->assertSame('00:10:00', $payload['activeTrackedTime']);
        $this->assertSame('00:20:00', $payload['trackedSpanTime']);
        $this->assertSame(600, $payload['trackingHealth']['active_tracked_seconds']);
        $this->assertSame(1200, $payload['trackingHealth']['saved_tracking_span_seconds']);

        Carbon::setTestNow();
    }

    public function test_timeline_tracked_time_handles_multiple_sessions_and_single_points(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);
        $employee = User::factory()->create(['status' => 'active']);
        $firstAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 08:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 09:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);
        $secondAttendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-31',
            'check_in_at' => Carbon::parse('2026-07-31 10:00:00', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-31 11:00:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $this->point($employee->id, $firstAttendance->id, 151, 11.000000, 77.000000, '2026-07-31 08:05:00', 'walking');
        $this->point($employee->id, $firstAttendance->id, 152, 11.000500, 77.000000, '2026-07-31 08:09:00', 'walking');
        $this->point($employee->id, $secondAttendance->id, 153, 11.010000, 77.000000, '2026-07-31 10:05:00', 'walking');

        $payload = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->post(route('dashboard.getTimeLineAjax'), [
                'userId' => $employee->id,
                'date' => '2026-07-31',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('00:04:00', $payload['totalTrackedTime']);
        $this->assertSame('00:04:00', $payload['activeTrackedTime']);
        $this->assertSame('00:04:00', $payload['trackedSpanTime']);
        $this->assertSame([$firstAttendance->id, $secondAttendance->id], $payload['attendanceIds']);
    }

    public function test_timeline_requires_existing_authorization(): void
    {
        $employee = User::factory()->create(['status' => 'active']);

        $this->post(route('dashboard.getTimeLineAjax'), [
            'userId' => $employee->id,
            'date' => '2026-07-22',
        ])->assertRedirect();
    }

    public function test_rejected_stationary_gps_point_updates_device_last_seen_and_keeps_device_online(): void
    {
        $employee = User::factory()->create(['status' => 'active']);
        $now = now();
        $attendance = Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => $now->toDateString(),
            'check_in_at' => $now->copy()->subMinutes(10),
            'status' => 'present',
        ]);

        $this->point($employee->id, $attendance->id, 901, 9.925200, 78.119800, $now->copy()->subMinutes(5)->toDateTimeString(), 'walking');

        $response = $this
            ->actingAs($employee)
            ->withoutMiddleware()
            ->postJson('/api/mobile/tracking/location', [
                'device_id' => 'test-device',
                'latitude' => 9.925201,
                'longitude' => 78.119801,
                'accuracy' => 5,
                'speed' => 0,
                'activity' => 'still',
                'is_gps_on' => true,
                'recorded_at' => $now->toDateTimeString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('saved', false)
            ->assertJsonPath('reason', 'distance_below_threshold');

        $device = \App\Models\EmployeeDevice::query()
            ->where('employee_id', $employee->id)
            ->where('device_id', 'test-device')
            ->first();

        $this->assertNotNull($device);
        $this->assertSame($now->toDateTimeString(), $device->last_seen_at?->toDateTimeString());
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
