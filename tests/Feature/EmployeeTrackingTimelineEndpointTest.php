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

    private function point(
        int $employeeId,
        int $attendanceId,
        int $id,
        float $latitude,
        float $longitude,
        string $recordedAt,
        string $activity,
        float $accuracy = 8,
        float $speed = 1
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
            'type' => 'location',
            'recorded_at' => Carbon::parse($recordedAt, 'Asia/Kolkata'),
        ]);
    }
}
