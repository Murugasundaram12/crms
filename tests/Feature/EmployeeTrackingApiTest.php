<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed for in-memory feature test database.');
        }

        parent::setUp();
    }

    public function test_get_timeline_ajax_returns_timeline_summary_and_activity_enriched_items(): void
    {
        $employee = User::factory()->create(['name' => 'John Doe']);
        $this->actingAs($employee);

        $date = '2026-08-07';
        $checkInAt = Carbon::parse($date . ' 09:00:00');
        $checkOutAt = Carbon::parse($date . ' 17:00:00');

        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'attendance_date' => $date,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'worked_minutes' => 480,
            'status' => 'present',
        ]);

        LocationTracking::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'latitude' => 13.0827,
            'longitude' => 80.2707,
            'accuracy' => 4.0,
            'speed' => 1.2,
            'activity' => 'walking',
            'type' => 'travelling',
            'recorded_at' => $checkInAt->copy()->addMinutes(10),
        ]);

        LocationTracking::create([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'latitude' => 13.0850,
            'longitude' => 80.2750,
            'accuracy' => 8.0,
            'speed' => 15.0,
            'activity' => 'in_vehicle',
            'type' => 'travelling',
            'recorded_at' => $checkInAt->copy()->addMinutes(30),
        ]);

        $response = $this->postJson(route('dashboard.getTimeLineAjax'), [
            'userId' => $employee->id,
            'date' => $date,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'employeeId',
            'employeeName',
            'timelineSummary' => [
                'check_in',
                'check_out',
                'working_time',
                'travel_time',
                'still_time',
                'total_distance',
                'tracking_points',
                'average_accuracy',
                'maximum_speed',
                'road_snapped',
            ],
            'timeLineItems',
        ]);

        $summary = $response->json('timelineSummary');
        $this->assertEquals(2, $summary['tracking_points']);
        $this->assertEquals('54 km/h', $summary['maximum_speed']);

        $items = $response->json('timeLineItems');
        $this->assertNotEmpty($items);
        $this->assertArrayHasKey('activity_code', $items[0]);
        $this->assertArrayHasKey('accuracy_badge_class', $items[0]);
    }

    public function test_reverse_geocode_endpoint_returns_cached_or_resolved_address(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('dashboard.reverseGeocode'), [
            'latitude' => 13.0827,
            'longitude' => 80.2707,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['address', 'latitude', 'longitude']);
    }
}
