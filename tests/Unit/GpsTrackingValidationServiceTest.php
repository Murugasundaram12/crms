<?php

namespace Tests\Unit;

use App\Models\LocationTracking;
use App\Services\GpsTrackingValidationService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GpsTrackingValidationServiceTest extends TestCase
{
    /**
     * Settings passed to validate() in tests.
     * gps_max_speed_mps of 25 m/s (~90 km/h) is the safety ceiling.
     * Movement-tier thresholds (20 m walking accuracy, 25 m vehicle accuracy,
     * 120° bearing) are Flutter constants not affected by these settings.
     */
    private array $settings = [
        'gps_max_accuracy_metres'        => 30,
        'gps_min_distance_metres'        => 5,
        'gps_max_speed_mps'              => 25,
        'gps_max_bearing_change_degrees' => 120,
        'gps_bearing_min_distance_metres'=> 10,
    ];

    // ─────────────────────────────────────────────────────────────
    // 1. Default constant sanity check
    // ─────────────────────────────────────────────────────────────

    public function test_default_minimum_distance_matches_tracking_checklist(): void
    {
        $this->assertSame(5.0,   GpsTrackingValidationService::DEFAULT_MIN_DISTANCE_METRES);
        $this->assertSame(30.0,  GpsTrackingValidationService::DEFAULT_MAX_ACCURACY_METRES);
        $this->assertSame(120.0, GpsTrackingValidationService::DEFAULT_MAX_BEARING_CHANGE_DEGREES);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Global accuracy ceiling (applies to ALL points incl. first)
    // ─────────────────────────────────────────────────────────────

    public function test_accuracy_greater_than_thirty_is_rejected(): void
    {
        $this->assertSame('accuracy_exceeded', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: 30.1),
            null, null, $this->settings
        )['reason']);
    }

    public function test_first_point_accuracy_exactly_thirty_is_accepted(): void
    {
        $result = $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: 30.0),
            null, null, $this->settings
        );
        $this->assertTrue($result['accepted']);
    }

    public function test_missing_accuracy_is_rejected(): void
    {
        $this->assertSame('accuracy_exceeded', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: null),
            null, null, $this->settings
        )['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. First valid point (no previous)
    // ─────────────────────────────────────────────────────────────

    public function test_first_valid_point_is_accepted_without_previous_location(): void
    {
        $result = $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: 10.0),
            null, null, $this->settings
        );
        $this->assertTrue($result['accepted']);
        $this->assertNull($result['distance_metres']);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Coordinate, GPS-off, mock-location validation
    // ─────────────────────────────────────────────────────────────

    public function test_invalid_and_zero_coordinates_are_rejected(): void
    {
        $v = $this->validator();
        $this->assertSame('invalid_coordinates', $v->validate($this->point(91,  77,  '10:00:00'), null, null, $this->settings)['reason']);
        $this->assertSame('invalid_coordinates', $v->validate($this->point(11,  181, '10:00:00'), null, null, $this->settings)['reason']);
        $this->assertSame('invalid_coordinates', $v->validate($this->point(0,   0,   '10:00:00'), null, null, $this->settings)['reason']);
    }

    public function test_gps_off_is_rejected(): void
    {
        $this->assertSame('gps_off', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', isGpsOn: false),
            null, null, $this->settings
        )['reason']);
    }

    public function test_mock_location_is_rejected(): void
    {
        $this->assertSame('mock_location', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', isMockLocation: true),
            null, null, $this->settings
        )['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 5. Duplicate location
    // ─────────────────────────────────────────────────────────────

    public function test_exact_duplicate_coordinates_are_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        $curr = $this->point(11.000000, 77.000000, '10:00:10');
        $this->assertSame('duplicate_location', $this->validator()->validate($curr, $prev, null, $this->settings)['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 6. Timestamp validation
    // ─────────────────────────────────────────────────────────────

    public function test_zero_and_negative_time_differences_are_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:10');
        $v    = $this->validator();

        $this->assertSame('invalid_timestamp', $v->validate(
            $this->point(11.000100, 77.000000, '10:00:10'), $prev, null, $this->settings
        )['reason']);

        $this->assertSame('invalid_timestamp', $v->validate(
            $this->point(11.000100, 77.000000, '10:00:09'), $prev, null, $this->settings
        )['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 7. Maximum speed safety ceiling
    // ─────────────────────────────────────────────────────────────

    public function test_backend_computed_speed_above_max_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~111 m in 1 s = 111 m/s >> 25 m/s limit
        $this->assertSame('speed_exceeded', $this->validator()->validate(
            $this->point(11.001000, 77.000000, '10:00:01', accuracy: 10, speed: 5.0),
            $prev, null, $this->settings
        )['reason']);
    }

    public function test_reported_speed_above_limit_is_rejected_even_when_computed_speed_is_low(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        $this->assertSame('speed_exceeded', $this->validator()->validate(
            $this->point(11.000100, 77.000000, '10:01:00', speed: 50),
            $prev, null, $this->settings
        )['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 8. speed < 0.5 rejected regardless of distance (cases 1 & 2)
    // ─────────────────────────────────────────────────────────────

    public function test_low_speed_low_distance_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~4.4 m in 20 s = 0.22 m/s
        $curr = $this->point(11.000040, 77.000000, '10:00:20', accuracy: 10, speed: 0.2);

        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        $this->assertFalse($result['accepted']);
        $this->assertSame('speed_below_threshold', $result['reason']);
    }

    public function test_low_speed_but_large_distance_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~111 m in 600 s = 0.185 m/s  (speed < 0.5, distance >> 5)
        $curr = $this->point(11.001000, 77.000000, '10:10:00', accuracy: 10, speed: 0.2);

        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        $this->assertFalse($result['accepted']);
        $this->assertSame('speed_below_threshold', $result['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 9. Walking tier: 0.5 <= speed < 5 (cases 3/4/5)
    // ─────────────────────────────────────────────────────────────

    public function test_walking_speed_with_accuracy_under_twenty_and_distance_at_least_five_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~11 m in 10 s = 1.1 m/s (walking), accuracy 15 m
        $curr = $this->point(11.000100, 77.000000, '10:00:10', accuracy: 15, speed: 1.1);
        $this->assertTrue($this->validator()->validate($curr, $prev, null, $this->settings)['accepted']);
    }

    public function test_walking_accuracy_boundary_twenty_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        $curr = $this->point(11.000100, 77.000000, '10:00:10', accuracy: 20.0, speed: 1.1);
        $this->assertTrue($this->validator()->validate($curr, $prev, null, $this->settings)['accepted']);
    }

    public function test_speed_at_exactly_zero_point_five_enters_walking_branch(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // need backend speed ~0.5 m/s: 5.5 m in 11 s → use 11.000050 lat (~5.5 m), 11s interval
        $curr = $this->point(11.000050, 77.000000, '10:00:11', accuracy: 15, speed: 0.5);
        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        // backend speed ~0.5 m/s, distance ~5.5 m >= 5, accuracy 15 <= 20 → accepted
        $this->assertTrue($result['accepted']);
    }

    public function test_walking_distance_boundary_five_metres_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // 11.000046 lat ≈ 5.1 m; 10 s → speed ≈ 0.51 m/s (walking)
        $curr = $this->point(11.000046, 77.000000, '10:00:10', accuracy: 10, speed: 1.0);
        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        $this->assertTrue($result['accepted']);
        $this->assertGreaterThanOrEqual(5.0, $result['distance_metres']);
    }

    public function test_walking_speed_with_accuracy_greater_than_twenty_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~11 m in 10 s = 1.1 m/s (walking), accuracy 22 m > 20 m
        $curr = $this->point(11.000100, 77.000000, '10:00:10', accuracy: 22, speed: 1.1);
        $this->assertSame('accuracy_exceeded', $this->validator()->validate($curr, $prev, null, $this->settings)['reason']);
    }

    public function test_walking_speed_with_distance_under_five_metres_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~4.4 m in 4 s = 1.1 m/s (walking), distance < 5 m
        $curr = $this->point(11.000040, 77.000000, '10:00:04', accuracy: 10, speed: 1.1);
        $this->assertSame('distance_below_threshold', $this->validator()->validate($curr, $prev, null, $this->settings)['reason']);
    }

    // ─────────────────────────────────────────────────────────────
    // 10. Vehicle tier: speed >= 5 (cases 6/7/8/9)
    // ─────────────────────────────────────────────────────────────

    public function test_vehicle_speed_with_good_conditions_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~111 m in 10 s = 11.1 m/s (vehicle), accuracy 24 m
        $curr = $this->point(11.001000, 77.000000, '10:00:10', accuracy: 24, speed: 11.1);
        $this->assertTrue($this->validator()->validate($curr, $prev, null, $this->settings)['accepted']);
    }

    public function test_speed_at_exactly_five_enters_vehicle_branch(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // 11.000090 lat ≈ 10 m; 2 s → speed = 5.0 m/s
        $curr = $this->point(11.000090, 77.000000, '10:00:02', accuracy: 10, speed: 5.0);
        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        $this->assertTrue($result['accepted']);
    }

    public function test_vehicle_accuracy_boundary_twenty_five_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        $curr = $this->point(11.001000, 77.000000, '10:00:10', accuracy: 25.0, speed: 11.1);
        $this->assertTrue($this->validator()->validate($curr, $prev, null, $this->settings)['accepted']);
    }

    public function test_vehicle_distance_boundary_ten_metres_is_accepted(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // 11.000100 ≈ 11 m; 2 s → speed ≈ 5.5 m/s (vehicle)
        $curr = $this->point(11.000100, 77.000000, '10:00:02', accuracy: 10, speed: 6.0);
        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        $this->assertTrue($result['accepted']);
        $this->assertGreaterThanOrEqual(10.0, $result['distance_metres']);
    }

    public function test_vehicle_speed_with_accuracy_greater_than_twenty_five_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~111 m in 10 s = 11.1 m/s (vehicle), accuracy 26 m > 25 m
        $curr = $this->point(11.001000, 77.000000, '10:00:10', accuracy: 26, speed: 11.1);
        $this->assertSame('accuracy_exceeded', $this->validator()->validate($curr, $prev, null, $this->settings)['reason']);
    }

    public function test_vehicle_speed_with_distance_under_ten_metres_is_rejected(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~7.7 m in 1 s = 7.7 m/s (vehicle), distance < 10 m
        $curr = $this->point(11.000070, 77.000000, '10:00:01', accuracy: 10, speed: 7.7);
        $this->assertSame('distance_below_threshold', $this->validator()->validate($curr, $prev, null, $this->settings)['reason']);
    }

    public function test_vehicle_speed_with_bearing_change_greater_than_120_degrees_is_rejected(): void
    {
        $v        = $this->validator();
        $prevPrev = $this->point(11.000000, 77.000000, '10:00:00', speed: 10);
        $prev     = $this->point(11.001000, 77.000000, '10:00:10', speed: 10);
        // U-turn back south: bearing delta = 180 deg > 120 deg
        $erratic  = $this->point(11.000000, 77.000000, '10:00:20', accuracy: 10, speed: 10);

        $result = $v->validate($erratic, $prev, $prevPrev, $this->settings);
        $this->assertFalse($result['accepted']);
        $this->assertSame('bearing_change_exceeded', $result['reason']);
        $this->assertGreaterThan(120, $result['bearing_difference']);
    }

    public function test_vehicle_bearing_change_under_120_is_accepted(): void
    {
        $v        = $this->validator();
        $prevPrev = $this->point(11.000000, 77.000000, '10:00:00', speed: 10);
        $prev     = $this->point(11.001000, 77.000000, '10:00:10', speed: 10);
        // NE turn: bearing delta ~45 deg <= 120 deg
        $smooth   = $this->point(11.001700, 77.000700, '10:00:20', accuracy: 10, speed: 10);

        $result = $v->validate($smooth, $prev, $prevPrev, $this->settings);
        $this->assertTrue($result['accepted']);
        $this->assertLessThanOrEqual(120, $result['bearing_difference']);
    }

    // ─────────────────────────────────────────────────────────────
    // 11. Backend speed is authoritative (device zero must not override)
    // ─────────────────────────────────────────────────────────────

    public function test_device_speed_zero_does_not_override_backend_computed_speed(): void
    {
        $prev = $this->point(11.000000, 77.000000, '10:00:00');
        // ~11 m in 10 s = 1.1 m/s backend; device says 0
        $curr = $this->point(11.000100, 77.000000, '10:00:10', accuracy: 15, speed: 0.0);

        $result = $this->validator()->validate($curr, $prev, null, $this->settings);
        // Backend speed = 1.1 m/s → walking branch → accepted
        $this->assertTrue($result['accepted']);
        $this->assertEqualsWithDelta(1.1, $result['speed_mps'], 0.1);
    }

    // ─────────────────────────────────────────────────────────────
    // 12. Bearing helper
    // ─────────────────────────────────────────────────────────────

    public function test_bearing_wraparound_is_normalized(): void
    {
        $this->assertSame(20.0, $this->validator()->bearingDifferenceDegrees(350, 10));
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function validator(): GpsTrackingValidationService
    {
        return new GpsTrackingValidationService();
    }

    private function point(
        ?float  $latitude,
        ?float  $longitude,
        string  $time,
        ?float  $accuracy       = 8,
        ?float  $speed          = 1,
        ?string $activity       = 'walking',
        string  $type           = 'travelling',
        bool    $isGpsOn        = true,
        bool    $isMockLocation = false,
    ): LocationTracking {
        $timestamp = Carbon::parse('2026-07-21 ' . $time);
        $tracking  = new LocationTracking();
        $tracking->forceFill([
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'accuracy'         => $accuracy,
            'speed'            => $speed,
            'activity'         => $activity,
            'type'             => $type,
            'is_gps_on'        => $isGpsOn,
            'is_mock_location' => $isMockLocation,
            'recorded_at'      => $timestamp,
            'created_at'       => $timestamp,
        ]);
        return $tracking;
    }
}
