<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\MobileApiController;
use App\Models\LocationTracking;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrackingIngestDriftSuppressionTest extends TestCase
{
    public function test_field_style_status_update_route_is_supported(): void
    {
        $route = $this->routeByUri('api/V1/attendance/statusUpdate');

        $this->assertNotNull($route);
    }

    public function test_bulk_offline_tracking_sync_routes_are_supported(): void
    {
        $this->assertNotNull($this->routeByUri('api/tracking/locations/bulk'));
        $this->assertNotNull($this->routeByUri('api/V1/attendance/statusUpdate/bulk'));
    }

    public function test_field_style_still_status_maps_to_still_tracking_type(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/V1/attendance/statusUpdate', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('still', $controller->validatedType($this->validPayload([
            'status' => 'still',
            'activity' => 'ActivityType.STILL',
        ])));

        $this->assertSame('still', $controller->validatedType($this->validPayload([
            'status' => 'ActivityType.STILL',
            'activity' => 'ActivityType.STILL',
        ])));
    }

    public function test_field_style_moving_status_maps_to_travelling_tracking_type(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/V1/attendance/statusUpdate', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('travelling', $controller->validatedType($this->validPayload([
            'status' => 'travelling',
            'activity' => 'ActivityType.IN_VEHICLE',
        ])));
    }

    public function test_explicit_tracking_type_takes_priority_over_field_style_status(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('travelling', $controller->validatedType($this->validPayload([
            'type' => 'travelling',
            'status' => 'still',
            'activity' => 'ActivityType.STILL',
        ])));
    }

    public function test_periodic_tracking_cannot_create_check_in_or_check_out_boundary_type(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('travelling', $controller->validatedType($this->validPayload([
            'type' => 'checked_out',
            'activity' => 'ActivityType.IN_VEHICLE',
        ])));

        $this->assertSame('still', $controller->validatedType($this->validPayload([
            'type' => 'checked_in',
            'activity' => 'ActivityType.STILL',
        ])));
    }

    public function test_activity_fallback_maps_still_to_still_tracking_type(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('still', $controller->validatedType($this->validPayload([
            'activity' => 'ActivityType.STILL',
        ])));
    }

    public function test_activity_fallback_maps_walking_and_vehicle_to_travelling_tracking_type(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedType(array $payload): string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['type'];
            }
        };

        $this->assertSame('travelling', $controller->validatedType($this->validPayload([
            'activity' => 'ActivityType.WALKING',
        ])));

        $this->assertSame('travelling', $controller->validatedType($this->validPayload([
            'activity' => 'ActivityType.IN_VEHICLE',
        ])));
    }

    public function test_offline_flag_is_normalized_for_queued_tracking_payload(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedOffline(array $payload): bool
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['is_offline'];
            }
        };

        $this->assertTrue($controller->validatedOffline($this->validPayload([
            'isOffline' => true,
        ])));

        $this->assertTrue($controller->validatedOffline($this->validPayload([
            'offline' => true,
        ])));
    }

    public function test_client_uuid_is_normalized_for_idempotent_tracking_retry(): void
    {
        $controller = new class extends MobileApiController {
            public function validatedClientUuid(array $payload): ?string
            {
                return $this->validateTrackingPayload(
                    Request::create('/api/tracking/location', 'POST', $payload),
                    'travelling'
                )['client_uuid'];
            }
        };

        $payload = $this->validPayload([
            'clientUuid' => 'gps-point-123',
        ]);
        unset($payload['client_uuid']);

        $this->assertSame('gps-point-123', $controller->validatedClientUuid($payload));
    }

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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => 9.9147387,
            'longitude' => 78.0979697,
            'accuracy' => 8,
            'isGpsOn' => true,
            'isWifiOn' => false,
            'isMock' => false,
            'batteryPercentage' => 67,
            'signalStrength' => '4G',
            'device_id' => 'UKQ1.231108.001',
            'client_uuid' => 'gps-point-default',
        ], $overrides);
    }

    private function routeByUri(string $uri): mixed
    {
        return collect(Route::getRoutes())->first(fn ($route) => (
            $route->uri() === $uri
            && in_array('POST', $route->methods(), true)
        ));
    }
}
