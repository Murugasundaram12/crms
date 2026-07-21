<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\MobileApiController;
use App\Models\LocationTracking;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrackingIngestDriftSuppressionTest extends TestCase
{
    public function test_stationary_drift_under_five_meters_is_not_inserted(): void
    {
        $controller = new class extends MobileApiController {
            public function suppress(?LocationTracking $lastTracking, array $payload): bool
            {
                return $this->shouldSuppressTrackingInsert($lastTracking, $payload);
            }
        };

        $lastTracking = $this->trackingPoint(11.016844, 76.955832);

        $this->assertTrue($controller->suppress($lastTracking, [
            'latitude' => 11.016854,
            'longitude' => 76.955842,
            'accuracy' => 19,
            'speed' => 0.2,
            'activity' => 'still',
            'type' => 'still',
        ]));
    }

    public function test_real_movement_over_five_meters_is_inserted(): void
    {
        $controller = new class extends MobileApiController {
            public function suppress(?LocationTracking $lastTracking, array $payload): bool
            {
                return $this->shouldSuppressTrackingInsert($lastTracking, $payload);
            }
        };

        $lastTracking = $this->trackingPoint(11.016844, 76.955832);

        $this->assertFalse($controller->suppress($lastTracking, [
            'latitude' => 11.017100,
            'longitude' => 76.955832,
            'accuracy' => 8,
            'speed' => 1.2,
            'activity' => 'travelling',
            'type' => 'travelling',
        ]));
    }

    public function test_very_poor_accuracy_is_not_inserted_as_a_route_point(): void
    {
        $controller = new class extends MobileApiController {
            public function suppress(?LocationTracking $lastTracking, array $payload): bool
            {
                return $this->shouldSuppressTrackingInsert($lastTracking, $payload);
            }
        };

        $lastTracking = $this->trackingPoint(11.016844, 76.955832);

        $this->assertTrue($controller->suppress($lastTracking, [
            'latitude' => 11.017500,
            'longitude' => 76.956500,
            'accuracy' => 80,
            'speed' => 1.2,
            'activity' => 'travelling',
            'type' => 'travelling',
        ]));

        $this->assertTrue($controller->suppress(null, [
            'latitude' => 11.017500,
            'longitude' => 76.956500,
            'accuracy' => 80,
            'speed' => 1.2,
            'activity' => 'travelling',
            'type' => 'travelling',
        ]));
    }

    public function test_suppressed_status_keeps_previous_coordinates(): void
    {
        $controller = new class extends MobileApiController {
            public function keepCoordinates(array $payload, LocationTracking $lastTracking): array
            {
                return $this->payloadWithStoredCoordinates($payload, $lastTracking);
            }
        };

        $lastTracking = $this->trackingPoint(11.016844, 76.955832);

        $payload = $controller->keepCoordinates([
            'latitude' => 11.016854,
            'longitude' => 76.955842,
            'accuracy' => 19,
        ], $lastTracking);

        $this->assertSame(11.016844, $payload['latitude']);
        $this->assertSame(76.955832, $payload['longitude']);
        $this->assertSame(19, $payload['accuracy']);
    }

    private function trackingPoint(float $latitude, float $longitude): LocationTracking
    {
        $tracking = new LocationTracking();
        $tracking->forceFill([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => 8,
            'speed' => 0,
            'activity' => 'still',
            'recorded_at' => Carbon::parse('2026-07-21 10:00:00'),
            'created_at' => Carbon::parse('2026-07-21 10:00:00'),
        ]);

        return $tracking;
    }
}
