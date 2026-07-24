<?php

namespace Tests\Unit;

use App\Models\LocationTracking;
use App\Http\Controllers\EmployeeTrackingController;
use App\Services\TimelineGpsProcessor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TimelineGpsProcessorTest extends TestCase
{
    public function test_it_handles_empty_gps_data(): void
    {
        $this->assertSame([], (new TimelineGpsProcessor())->filter(collect()));
    }

    public function test_it_handles_single_point_data(): void
    {
        $point = $this->point(11.016844, 76.955832, '2026-07-21 10:00:00', id: 1);

        $filtered = (new TimelineGpsProcessor())->filter(collect([$point]));

        $this->assertCount(1, $filtered);
        $this->assertSame(1, $filtered[0]->id);
    }

    public function test_it_filters_invalid_duplicate_inaccurate_and_stationary_points(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(null, 76.955832, '2026-07-21 10:00:00'),
            $this->point(0, 0, '2026-07-21 10:00:01'),
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:02'),
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:03'),
            $this->point(11.016900, 76.955832, '2026-07-21 10:00:02'),
            $this->point(11.017000, 76.955832, '2026-07-21 10:00:04', accuracy: 35),
            $this->point(11.017050, 76.955832, '2026-07-21 10:00:04', accuracy: null),
            $this->point(11.017080, 76.955832, '2026-07-21 10:00:04', accuracy: 0),
            $this->point(11.017100, 76.955832, '2026-07-21 10:00:04', isMockLocation: true),
            $this->point(11.016880, 76.955832, '2026-07-21 10:00:05', speed: 0),
            $this->point(11.017020, 76.955832, '2026-07-21 10:00:06'),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 20,
            'simplify_after_points' => 1000,
        ]);

        $this->assertCount(2, $filtered);
        $this->assertSame(11.016844, (float) $filtered[0]->latitude);
        $this->assertSame(11.017020, (float) $filtered[1]->latitude);
    }

    public function test_it_does_not_remove_non_consecutive_duplicate_coordinates(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:00', id: 1),
            $this->point(11.017100, 76.955832, '2026-07-21 10:01:00', id: 2),
            $this->point(11.016844, 76.955832, '2026-07-21 10:02:00', id: 3),
            $this->point(11.017300, 76.955832, '2026-07-21 10:03:00', id: 4),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 20,
        ]);

        $this->assertSame([1, 2, 3, 4], array_map(fn (LocationTracking $point) => $point->id, $filtered));
    }

    public function test_it_orders_timeline_points_by_created_at_then_id(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(11.017300, 76.955832, '2026-07-21 10:03:00', id: 4),
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:00', id: 1),
            $this->point(11.017100, 76.955832, '2026-07-21 10:01:00', id: 2),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 20,
        ]);

        $this->assertSame([1, 2, 4], array_map(fn (LocationTracking $point) => $point->id, $filtered));
    }

    public function test_it_simplifies_large_polyline_point_sets(): void
    {
        $processor = new TimelineGpsProcessor();
        $points = Collection::times(1001, function (int $index) {
            return $this->point(11 + ($index * 0.00001), 76.955832, '2026-07-21 10:00:00', secondsOffset: $index);
        });

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 0,
            'max_accuracy_meters' => 20,
            'simplify_after_points' => 1000,
            'simplification_tolerance_meters' => 5,
        ]);

        $this->assertLessThan(1001, count($filtered));
        $this->assertSame((float) $points->first()->latitude, (float) $filtered[0]->latitude);
        $this->assertSame((float) $points->last()->latitude, (float) $filtered[count($filtered) - 1]->latitude);
    }

    public function test_polyline_simplification_keeps_important_movement_turns(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1),
            $this->point(11.001000, 77.000000, '2026-07-21 10:02:00', id: 2),
            $this->point(11.002000, 77.001500, '2026-07-21 10:04:00', id: 3),
            $this->point(11.003000, 77.000000, '2026-07-21 10:06:00', id: 4),
            $this->point(11.004000, 77.000000, '2026-07-21 10:08:00', id: 5),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 0,
            'max_accuracy_meters' => 20,
            'simplify_after_points' => 3,
            'simplification_tolerance_meters' => 30,
            'max_computed_speed_kmh' => 120,
        ]);

        $ids = array_map(fn (LocationTracking $point) => $point->id, $filtered);

        $this->assertContains(1, $ids);
        $this->assertContains(3, $ids);
        $this->assertContains(5, $ids);
    }

    public function test_it_uses_device_bearing_to_reject_short_gps_drift(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:00', bearing: 0),
            $this->point(11.017000, 76.955832, '2026-07-21 10:00:10', bearing: 0),
            $this->point(11.017020, 76.955850, '2026-07-21 10:00:20', bearing: 120),
            $this->point(11.017200, 76.955832, '2026-07-21 10:00:30', bearing: 0),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 20,
            'bearing_drift_distance_meters' => 10,
            'bearing_change_degrees' => 60,
            'simplify_after_points' => 1000,
        ]);

        $this->assertCount(3, $filtered);
        $this->assertSame(11.017200, (float) $filtered[2]->latitude);
    }

    public function test_it_rejects_impossible_computed_speed_jumps(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(11.016844, 76.955832, '2026-07-21 10:00:00'),
            $this->point(11.016950, 76.955832, '2026-07-21 10:00:10'),
            $this->point(11.026950, 76.955832, '2026-07-21 10:00:15'),
            $this->point(11.017100, 76.955832, '2026-07-21 10:00:30'),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 20,
            'max_computed_speed_kmh' => 80,
            'simplify_after_points' => 1000,
        ]);

        $this->assertCount(3, $filtered);
        $this->assertSame(11.017100, (float) $filtered[2]->latitude);
    }

    public function test_it_reduces_stationary_oscillating_drift_clusters(): void
    {
        $processor = new TimelineGpsProcessor();

        $points = collect([
            $this->point(10.3688475, 77.9819837, '2026-07-21 12:07:42', accuracy: 29, speed: 0.02, id: 1),
            $this->point(10.3685750, 77.9820478, '2026-07-21 12:10:39', accuracy: 4, speed: 0.04, id: 2),
            $this->point(10.3688486, 77.9819918, '2026-07-21 12:14:29', accuracy: 5, speed: 0.04, id: 3),
            $this->point(10.3685564, 77.9820364, '2026-07-21 12:26:39', accuracy: 7, speed: 0.11, id: 4),
            $this->point(10.3690437, 77.9820217, '2026-07-21 12:27:44', accuracy: 6, speed: 0.16, id: 5),
            $this->point(10.3683821, 77.9820397, '2026-07-21 13:07:18', accuracy: 2, speed: 0.02, id: 6),
            $this->point(10.3684094, 77.9820021, '2026-07-21 13:11:21', accuracy: 5, speed: 0.01, id: 7),
            $this->point(10.3687602, 77.9820740, '2026-07-21 13:22:04', accuracy: 10, speed: 0.26, id: 8),
        ]);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 30,
            'max_accuracy_meters' => 50,
            'max_bearing_change_degrees' => 0,
            'max_computed_speed_kmh' => 120,
        ]);

        $this->assertLessThanOrEqual(2, count($filtered));
        $this->assertSame(1, $filtered[0]->id);
    }

    public function test_large_datasets_perform_acceptably(): void
    {
        $processor = new TimelineGpsProcessor();
        $points = Collection::times(5000, function (int $index) {
            return $this->point(11 + ($index * 0.00001), 76.955832, '2026-07-21 10:00:00', secondsOffset: $index, id: $index);
        });

        $startedAt = microtime(true);

        $filtered = $processor->filter($points, [
            'minimum_distance_meters' => 0,
            'max_accuracy_meters' => 20,
            'simplify_after_points' => 1000,
            'simplification_tolerance_meters' => 5,
            'max_computed_speed_kmh' => 200,
        ]);

        $this->assertLessThan(5.0, microtime(true) - $startedAt);
        $this->assertNotEmpty($filtered);
        $this->assertLessThan(5000, count($filtered));
    }

    public function test_timeline_date_bounds_use_the_configured_timezone(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);

        $controller = new EmployeeTrackingController();
        $method = new \ReflectionMethod($controller, 'timelineDateBounds');
        $method->setAccessible(true);

        [$start, $end] = $method->invoke($controller, '2026-07-20');

        $this->assertSame('Asia/Kolkata', $start->timezoneName);
        $this->assertSame('2026-07-20 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-20 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    private function point(?float $latitude, ?float $longitude, string $time, ?float $accuracy = 10, ?float $speed = 1, int $secondsOffset = 0, ?float $bearing = null, ?int $id = null, bool $isMockLocation = false): LocationTracking
    {
        $timestamp = Carbon::parse($time)->addSeconds($secondsOffset);
        $tracking = new LocationTracking();
        $tracking->forceFill([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'bearing' => $bearing,
            'is_mock_location' => $isMockLocation,
            'recorded_at' => $timestamp,
            'created_at' => $timestamp,
        ]);
        if ($id !== null) {
            $tracking->id = $id;
        }

        return $tracking;
    }
}
