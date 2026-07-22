<?php

namespace Tests\Unit;

use App\Models\LocationTracking;
use App\Services\GpsTrackingValidationService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GpsTrackingValidationServiceTest extends TestCase
{
    private array $settings = [
        'gps_max_accuracy_metres' => 8,
        'gps_min_distance_metres' => 5,
        'gps_max_speed_mps' => 25,
        'gps_max_bearing_change_degrees' => 45,
        'gps_bearing_min_distance_metres' => 10,
    ];

    public function test_first_valid_point_is_accepted_without_previous_location(): void
    {
        $result = $this->validator()->validate($this->point(11.000000, 77.000000, '10:00:00'), null, null, $this->settings);

        $this->assertTrue($result['accepted']);
        $this->assertNull($result['distance_metres']);
    }

    public function test_default_minimum_distance_matches_tracking_checklist(): void
    {
        $this->assertSame(30.0, GpsTrackingValidationService::DEFAULT_MIN_DISTANCE_METRES);
    }

    public function test_accuracy_greater_than_eight_is_rejected_and_equal_eight_is_accepted(): void
    {
        $this->assertSame('accuracy_exceeded', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: 8.1),
            null,
            null,
            $this->settings
        )['reason']);

        $this->assertTrue($this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: 8),
            null,
            null,
            $this->settings
        )['accepted']);
    }

    public function test_missing_accuracy_is_rejected(): void
    {
        $this->assertSame('accuracy_exceeded', $this->validator()->validate(
            $this->point(11.000000, 77.000000, '10:00:00', accuracy: null),
            null,
            null,
            $this->settings
        )['reason']);
    }

    public function test_invalid_latitude_longitude_and_zero_coordinates_are_rejected(): void
    {
        $validator = $this->validator();

        $this->assertSame('invalid_coordinates', $validator->validate($this->point(91, 77, '10:00:00'), null, null, $this->settings)['reason']);
        $this->assertSame('invalid_coordinates', $validator->validate($this->point(11, 181, '10:00:00'), null, null, $this->settings)['reason']);
        $this->assertSame('invalid_coordinates', $validator->validate($this->point(0, 0, '10:00:00'), null, null, $this->settings)['reason']);
    }

    public function test_exact_duplicate_coordinates_are_rejected(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00');
        $current = $this->point(11.000000, 77.000000, '10:00:10');

        $this->assertSame('duplicate_location', $this->validator()->validate($current, $previous, null, $this->settings)['reason']);
    }

    public function test_movement_at_or_below_five_metres_is_rejected_and_above_five_metres_is_accepted(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00');
        $below = $this->point(11.000040, 77.000000, '10:00:10');
        $above = $this->point(11.000046, 77.000000, '10:00:10');

        $this->assertSame('distance_below_threshold', $this->validator()->validate($below, $previous, null, $this->settings)['reason']);
        $this->assertTrue($this->validator()->validate($above, $previous, null, $this->settings)['accepted']);
    }

    public function test_zero_and_negative_time_differences_are_rejected(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:10');

        $this->assertSame('invalid_timestamp', $this->validator()->validate(
            $this->point(11.000100, 77.000000, '10:00:10'),
            $previous,
            null,
            $this->settings
        )['reason']);

        $this->assertSame('invalid_timestamp', $this->validator()->validate(
            $this->point(11.000100, 77.000000, '10:00:09'),
            $previous,
            null,
            $this->settings
        )['reason']);
    }

    public function test_valid_walking_speed_is_accepted_and_speed_above_limit_is_rejected(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00');

        $this->assertTrue($this->validator()->validate(
            $this->point(11.000100, 77.000000, '10:00:20'),
            $previous,
            null,
            $this->settings
        )['accepted']);

        $this->assertSame('speed_exceeded', $this->validator()->validate(
            $this->point(11.002000, 77.000000, '10:00:05'),
            $previous,
            null,
            $this->settings
        )['reason']);
    }

    public function test_reported_speed_above_limit_is_rejected_even_when_computed_speed_is_low(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00');

        $this->assertSame('speed_exceeded', $this->validator()->validate(
            $this->point(11.000100, 77.000000, '10:01:00', speed: 30),
            $previous,
            null,
            $this->settings
        )['reason']);
    }

    public function test_still_employee_gps_drift_under_five_metres_is_rejected(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00', activity: 'still', speed: 0);

        $this->assertSame('distance_below_threshold', $this->validator()->validate(
            $this->point(11.000040, 77.000000, '10:00:20', activity: 'still', speed: 0.2),
            $previous,
            null,
            $this->settings
        )['reason']);
    }

    public function test_tracking_type_still_is_stationary_even_when_activity_reports_walking(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00', activity: 'walking', speed: 0, type: 'still');

        $this->assertSame('distance_below_threshold', $this->validator()->validate(
            $this->point(11.000040, 77.000000, '10:00:20', activity: 'walking', speed: 0.2, type: 'still'),
            $previous,
            null,
            $this->settings
        )['reason']);
    }

    public function test_sudden_jump_is_rejected_but_same_distance_over_sufficient_time_is_accepted(): void
    {
        $previous = $this->point(11.000000, 77.000000, '10:00:00');

        $this->assertSame('speed_exceeded', $this->validator()->validate(
            $this->point(11.003150, 77.000000, '10:00:05'),
            $previous,
            null,
            $this->settings
        )['reason']);

        $this->assertTrue($this->validator()->validate(
            $this->point(11.003150, 77.000000, '10:05:00'),
            $previous,
            null,
            $this->settings
        )['accepted']);
    }

    public function test_bearing_wraparound_is_normalized(): void
    {
        $this->assertSame(20.0, $this->validator()->bearingDifferenceDegrees(350, 10));
    }

    public function test_extreme_bearing_change_is_marked_but_not_rejected_by_bearing_alone(): void
    {
        $validator = $this->validator();
        $previousPrevious = $this->point(11.000000, 77.000000, '10:00:00');
        $previous = $this->point(11.000200, 77.000000, '10:00:20');
        $current = $this->point(10.999900, 77.000000, '10:00:50');

        $result = $validator->validate($current, $previous, $previousPrevious, $this->settings);
        $this->assertTrue($result['accepted']);
        $this->assertGreaterThan(45, $result['bearing_difference']);

        $shortSegmentCurrent = $this->point(11.000190, 77.000000, '10:00:50');
        $this->assertNull($validator->validate($shortSegmentCurrent, $previous, $previousPrevious, $this->settings)['bearing_difference']);
    }

    private function validator(): GpsTrackingValidationService
    {
        return new GpsTrackingValidationService();
    }

    private function point(?float $latitude, ?float $longitude, string $time, ?float $accuracy = 8, ?float $speed = 1, ?string $activity = 'walking', string $type = 'travelling'): LocationTracking
    {
        $timestamp = Carbon::parse('2026-07-21 ' . $time);
        $tracking = new LocationTracking();
        $tracking->forceFill([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'activity' => $activity,
            'type' => $type,
            'is_mock_location' => false,
            'recorded_at' => $timestamp,
            'created_at' => $timestamp,
        ]);

        return $tracking;
    }
}
