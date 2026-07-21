<?php

namespace Tests\Unit;

use App\Models\LocationTracking;
use App\Services\TimelineGpsProcessor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TimelineGpsProcessorTest extends TestCase
{
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

    private function point(?float $latitude, ?float $longitude, string $time, ?float $accuracy = 10, ?float $speed = 1, int $secondsOffset = 0): LocationTracking
    {
        $timestamp = Carbon::parse($time)->addSeconds($secondsOffset);
        $tracking = new LocationTracking();
        $tracking->forceFill([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'recorded_at' => $timestamp,
            'created_at' => $timestamp,
        ]);

        return $tracking;
    }
}
