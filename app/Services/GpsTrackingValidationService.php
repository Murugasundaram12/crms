<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LocationTracking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class GpsTrackingValidationService
{
    /*
    |--------------------------------------------------------------------------
    | Default High-Precision GPS Validation Settings
    |--------------------------------------------------------------------------
    */

    /**
     * Maximum allowed GPS accuracy radius in metres.
     * Reduced from 30.0m to 15.0m to prevent jumps to parallel streets (e.g. Bypass Road).
     */
    public const DEFAULT_MAX_ACCURACY_METRES = 15.0;

    /**
     * Minimum distance in metres required to consider movement valid.
     * Increased from 5.0m to 8.0m to filter out stationary GPS drift jitter.
     */
    public const DEFAULT_MIN_DISTANCE_METRES = 8.0;

    public const DEFAULT_MAX_SPEED_MPS = 33.33333333; // 120 km/h

    public const DEFAULT_MAX_WALKING_SPEED_MPS = 5.0; // 18 km/h

    public const DEFAULT_MAX_BEARING_CHANGE_DEGREES = 120.0;

    public const DEFAULT_BEARING_MIN_DISTANCE_METRES = 10.0;

    public const DEFAULT_TRACKING_INTERVAL_SECONDS = 30;

    public const DEFAULT_MAX_INACTIVE_GAP_SECONDS = 600;

    public const DEFAULT_DOUGLAS_PEUCKER_TOLERANCE_METRES = 15.0;

    /*
    |--------------------------------------------------------------------------
    | GPS Jump Protection
    |--------------------------------------------------------------------------
    */

    public const DEFAULT_MAX_JUMP_DISTANCE_METRES = 100.0;

    /*
    |--------------------------------------------------------------------------
    | Stationary GPS protection
    |--------------------------------------------------------------------------
    */

    public const DEFAULT_STATIONARY_SPEED_MPS = 0.5;

    public const DEFAULT_STATIONARY_DISTANCE_METRES = 8.0;

    private static ?array $cachedSettings = null;

    /**
     * Get all GPS settings merged with database and runtime overrides.
     */
    public function settings(array $overrides = []): array
    {
        $settings = [
            'gps_max_accuracy_metres' => self::DEFAULT_MAX_ACCURACY_METRES,

            'gps_min_distance_metres' => self::DEFAULT_MIN_DISTANCE_METRES,

            'gps_max_speed_mps' => self::DEFAULT_MAX_SPEED_MPS,

            'gps_max_walking_speed_mps' => self::DEFAULT_MAX_WALKING_SPEED_MPS,

            'gps_max_bearing_change_degrees' => self::DEFAULT_MAX_BEARING_CHANGE_DEGREES,

            'gps_bearing_min_distance_metres' => self::DEFAULT_BEARING_MIN_DISTANCE_METRES,

            'tracking_interval_seconds' => self::DEFAULT_TRACKING_INTERVAL_SECONDS,

            'gps_max_inactive_gap_seconds' => self::DEFAULT_MAX_INACTIVE_GAP_SECONDS,

            'large_gap_distance_meters' => 2000.0,

            'gps_douglas_peucker_tolerance_metres'
                => self::DEFAULT_DOUGLAS_PEUCKER_TOLERANCE_METRES,

            'gps_max_jump_distance_metres'
                => self::DEFAULT_MAX_JUMP_DISTANCE_METRES,

            'mock_location_allowed' => false,
        ];

        return [
            ...$settings,
            ...$this->databaseSettings(),
            ...array_filter(
                $overrides,
                fn ($value) => $value !== null
            ),
        ];
    }

    /**
     * Main validation method.
     */
    public function validate(
        array|LocationTracking $current,
        ?LocationTracking $previous = null,
        ?LocationTracking $previousPrevious = null,
        array $overrides = []
    ): array {
        return $this->validateWithSettings(
            $current,
            $previous,
            $previousPrevious,
            $this->settings($overrides)
        );
    }

    /**
     * Validate GPS point using provided settings.
     */
    public function validateWithSettings(
        array|LocationTracking $current,
        ?LocationTracking $previous = null,
        ?LocationTracking $previousPrevious = null,
        array $settings = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. Current point data extraction
        |--------------------------------------------------------------------------
        */

        $currentPoint = $this->pointData($current);

        /*
        |--------------------------------------------------------------------------
        | 2. Validate basic coordinates
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->hasValidCoordinates(
                $currentPoint['latitude'],
                $currentPoint['longitude']
            )
        ) {
            return $this->rejected('invalid_coordinates');
        }

        /*
        |--------------------------------------------------------------------------
        | 3. GPS must be ON
        |--------------------------------------------------------------------------
        */

        if (! (bool) ($currentPoint['is_gps_on'] ?? true)) {
            return $this->rejected('gps_off');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Mock location protection
        |--------------------------------------------------------------------------
        */

        if (
            (bool) ($currentPoint['is_mock_location'] ?? false)
            && ! (bool) ($settings['mock_location_allowed'] ?? false)
        ) {
            return $this->rejected('mock_location');
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Strict High-Accuracy Filter (<= 15m Accept, > 15m Reject)
        |--------------------------------------------------------------------------
        |
        | Points with accuracy > 15m are rejected because satellite multipath
        | reflections near buildings will cause coordinates to jump 20m onto
        | parallel streets (e.g. Bypass Road).
        |
        */

        $globalMaxAccuracy = (float) (
            $settings['gps_max_accuracy_metres']
            ?? self::DEFAULT_MAX_ACCURACY_METRES
        );

        if (
            $currentPoint['accuracy'] === null
            || (float) $currentPoint['accuracy'] <= 0
            || (float) $currentPoint['accuracy'] > $globalMaxAccuracy
        ) {
            return $this->rejected('accuracy_exceeded');
        }

        /*
        |--------------------------------------------------------------------------
        | 6. First valid GPS point handling
        |--------------------------------------------------------------------------
        */

        if (! $previous) {
            return $this->accepted([
                'distance_metres' => null,
                'time_difference_seconds' => null,
                'speed_mps' => null,
                'speed_kmph' => null,
                'bearing' => null,
                'bearing_difference' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Previous point data extraction
        |--------------------------------------------------------------------------
        */

        $previousPoint = $this->pointData($previous);

        /*
        |--------------------------------------------------------------------------
        | 8. Duplicate coordinate protection
        |--------------------------------------------------------------------------
        */

        if ($this->sameCoordinates($previousPoint, $currentPoint)) {
            return $this->rejected('duplicate_location');
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Timestamp validation
        |--------------------------------------------------------------------------
        */

        $timeDifferenceSeconds = $this->timeDifferenceSeconds(
            $previousPoint['recorded_at'],
            $currentPoint['recorded_at']
        );

        if ($timeDifferenceSeconds <= 0) {
            return $this->rejected(
                'invalid_timestamp',
                [
                    'time_difference_seconds' => $timeDifferenceSeconds,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Haversine Distance calculation
        |--------------------------------------------------------------------------
        */

        $distanceMetres = $this->distanceMetres(
            (float) $previousPoint['latitude'],
            (float) $previousPoint['longitude'],
            (float) $currentPoint['latitude'],
            (float) $currentPoint['longitude']
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Backend calculated speed
        |--------------------------------------------------------------------------
        */

        $speedMps = $distanceMetres / $timeDifferenceSeconds;

        $speedKmph = $speedMps * 3.6;

        $maxSpeedMps = (float) (
            $settings['gps_max_speed_mps']
            ?? self::DEFAULT_MAX_SPEED_MPS
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Maximum speed protection (120 km/h max)
        |--------------------------------------------------------------------------
        */

        if (
            $speedMps > $maxSpeedMps
            ||
            (
                $currentPoint['speed'] !== null
                &&
                (float) $currentPoint['speed'] > $maxSpeedMps
            )
        ) {
            return $this->rejected(
                'speed_exceeded',
                [
                    'distance_metres' => $distanceMetres,
                    'time_difference_seconds' => $timeDifferenceSeconds,
                    'speed_mps' => $speedMps,
                    'speed_kmph' => $speedKmph,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 13. GPS Jump Protection
        |--------------------------------------------------------------------------
        */

        $configuredMaxJumpDistance = (float) (
            $settings['gps_max_jump_distance_metres']
            ?? self::DEFAULT_MAX_JUMP_DISTANCE_METRES
        );

        $dynamicMaxJumpDistance = max(
            $configuredMaxJumpDistance,
            $maxSpeedMps * $timeDifferenceSeconds * 1.5
        );

        $dynamicMaxJumpDistance = min(
            $dynamicMaxJumpDistance,
            1000.0
        );

        if ($distanceMetres > $dynamicMaxJumpDistance) {
            return $this->rejected(
                'gps_jump_detected',
                [
                    'distance_metres' => $distanceMetres,
                    'time_difference_seconds' => $timeDifferenceSeconds,
                    'speed_mps' => $speedMps,
                    'speed_kmph' => $speedKmph,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 14. Bearing calculation
        |--------------------------------------------------------------------------
        */

        $bearing = $this->bearingDegrees(
            (float) $previousPoint['latitude'],
            (float) $previousPoint['longitude'],
            (float) $currentPoint['latitude'],
            (float) $currentPoint['longitude']
        );

        $bearingDifference = null;

        /*
        |--------------------------------------------------------------------------
        | 15. Previous bearing validation (Zig-zag jump protection)
        |--------------------------------------------------------------------------
        */

        if ($previousPrevious) {
            $previousPreviousPoint = $this->pointData(
                $previousPrevious
            );

            $previousSegmentDistance = $this->distanceMetres(
                (float) $previousPreviousPoint['latitude'],
                (float) $previousPreviousPoint['longitude'],
                (float) $previousPoint['latitude'],
                (float) $previousPoint['longitude']
            );

            $bearingMinimumDistance = (float) (
                $settings['gps_bearing_min_distance_metres']
                ?? self::DEFAULT_BEARING_MIN_DISTANCE_METRES
            );

            if (
                $previousSegmentDistance >= $bearingMinimumDistance
                &&
                $distanceMetres >= $bearingMinimumDistance
            ) {
                $previousBearing = $this->bearingDegrees(
                    (float) $previousPreviousPoint['latitude'],
                    (float) $previousPreviousPoint['longitude'],
                    (float) $previousPoint['latitude'],
                    (float) $previousPoint['longitude']
                );

                $bearingDifference = $this->bearingDifferenceDegrees(
                    $previousBearing,
                    $bearing
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 16. Metrics payload
        |--------------------------------------------------------------------------
        */

        $metrics = [
            'distance_metres' => $distanceMetres,
            'time_difference_seconds' => $timeDifferenceSeconds,
            'speed_mps' => $speedMps,
            'speed_kmph' => $speedKmph,
            'bearing' => $bearing,
            'bearing_difference' => $bearingDifference,
        ];

        $evalSpeed = $speedMps;
        $accuracy = (float) $currentPoint['accuracy'];

        /*
        |--------------------------------------------------------------------------
        | 17. Stationary Filter (< 0.5 m/s OR < 8.0m movement)
        |--------------------------------------------------------------------------
        |
        | Prevents GPS hardware jitter from logging spurious points while standing still.
        |
        */

        $minStationaryDistance = (float) (
            $settings['gps_min_distance_metres']
            ?? self::DEFAULT_STATIONARY_DISTANCE_METRES
        );

        if ($evalSpeed < self::DEFAULT_STATIONARY_SPEED_MPS || $distanceMetres < $minStationaryDistance) {
            return $this->rejected(
                'speed_below_threshold',
                $metrics
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 18. Walking / Slow movement (0.5 <= speed < 5 m/s)
        |--------------------------------------------------------------------------
        */

        if (
            $evalSpeed >= self::DEFAULT_STATIONARY_SPEED_MPS
            &&
            $evalSpeed < self::DEFAULT_MAX_WALKING_SPEED_MPS
        ) {
            if ($accuracy > $globalMaxAccuracy) {
                return $this->rejected(
                    'accuracy_exceeded',
                    $metrics
                );
            }

            if ($distanceMetres < $minStationaryDistance) {
                return $this->rejected(
                    'distance_below_threshold',
                    $metrics
                );
            }

            return $this->accepted($metrics);
        }

        /*
        |--------------------------------------------------------------------------
        | 19. Vehicle movement (speed >= 5 m/s)
        |--------------------------------------------------------------------------
        */

        if ($evalSpeed >= self::DEFAULT_MAX_WALKING_SPEED_MPS) {
            if ($accuracy > $globalMaxAccuracy) {
                return $this->rejected(
                    'accuracy_exceeded',
                    $metrics
                );
            }

            if ($distanceMetres < 10.0) {
                return $this->rejected(
                    'distance_below_threshold',
                    $metrics
                );
            }

            $maxBearingChange = (float) (
                $settings['gps_max_bearing_change_degrees']
                ?? self::DEFAULT_MAX_BEARING_CHANGE_DEGREES
            );

            if (
                $bearingDifference !== null
                &&
                $bearingDifference > $maxBearingChange
            ) {
                return $this->rejected(
                    'bearing_change_exceeded',
                    $metrics
                );
            }

            return $this->accepted($metrics);
        }

        /*
        |--------------------------------------------------------------------------
        | 20. Fallback
        |--------------------------------------------------------------------------
        */

        return $this->rejected(
            'invalid_movement',
            $metrics
        );
    }

    /**
     * Validate standalone GPS quality without previous fix context.
     */
    public function hasStandaloneQuality(
        array|LocationTracking $point,
        array $overrides = []
    ): bool {
        $result = $this->validate(
            $point,
            null,
            null,
            $overrides
        );

        return (bool) $result['accepted'];
    }

    /**
     * Calculate Haversine distance between two GPS coordinates in metres.
     */
    public function distanceMetres(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadiusMetres = 6371000;

        $latDistance = deg2rad($lat2 - $lat1);
        $lngDistance = deg2rad($lng2 - $lng1);

        $a =
            sin($latDistance / 2) ** 2
            +
            cos(deg2rad($lat1))
            *
            cos(deg2rad($lat2))
            *
            sin($lngDistance / 2) ** 2;

        return $earthRadiusMetres
            *
            (
                2
                *
                atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                )
            );
    }

    /**
     * Calculate bearing between two GPS coordinates in degrees (0-360).
     */
    public function bearingDegrees(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $fromLat = deg2rad($lat1);
        $toLat = deg2rad($lat2);
        $lngDelta = deg2rad($lng2 - $lng1);

        $y = sin($lngDelta) * cos($toLat);

        $x =
            cos($fromLat) * sin($toLat)
            -
            sin($fromLat)
            *
            cos($toLat)
            *
            cos($lngDelta);

        return fmod(
            rad2deg(atan2($y, $x)) + 360,
            360
        );
    }

    /**
     * Calculate smallest bearing difference between two bearings (0-180 degrees).
     */
    public function bearingDifferenceDegrees(
        float $previousBearing,
        float $currentBearing
    ): float {
        $difference = abs($currentBearing - $previousBearing);

        return min(
            $difference,
            360 - $difference
        );
    }

    /**
     * Snap raw GPS points to actual road network using Google Snap to Roads API.
     *
     * Call this method when rendering map polylines in the web dashboard timeline.
     */
    public function snapToRoads(array $points, string $googleApiKey): array
    {
        if (count($points) < 2) {
            return $points;
        }

        $pathString = implode('|', array_map(function ($p) {
            $lat = is_object($p) ? $p->latitude : $p['latitude'];
            $lng = is_object($p) ? $p->longitude : $p['longitude'];
            return "{$lat},{$lng}";
        }, $points));

        $url = "https://roads.googleapis.com/v1/snapToRoads?path={$pathString}&interpolate=true&key={$googleApiKey}";

        try {
            $response = @file_get_contents($url);
            if (! $response) {
                return $points;
            }

            $data = json_decode($response, true);
            if (! isset($data['snappedPoints'])) {
                return $points;
            }

            return array_map(function ($sp) {
                return [
                    'latitude' => $sp['location']['latitude'],
                    'longitude' => $sp['location']['longitude'],
                    'originalIndex' => $sp['originalIndex'] ?? null,
                    'placeId' => $sp['placeId'] ?? null,
                ];
            }, $data['snappedPoints']);
        } catch (\Throwable) {
            return $points;
        }
    }

    /**
     * Normalize GPS point data from array or LocationTracking model.
     */
    private function pointData(
        array|LocationTracking $point
    ): array {
        if ($point instanceof LocationTracking) {
            return [
                'latitude' =>
                    $point->latitude !== null
                        ? (float) $point->latitude
                        : null,

                'longitude' =>
                    $point->longitude !== null
                        ? (float) $point->longitude
                        : null,

                'accuracy' =>
                    $point->accuracy !== null
                        ? (float) $point->accuracy
                        : null,

                'speed' =>
                    $point->speed !== null
                        ? (float) $point->speed
                        : null,

                'activity' => $point->activity,

                'type' => $point->type,

                'is_gps_on' =>
                    (bool) ($point->is_gps_on ?? true),

                'is_mock_location' =>
                    (bool) ($point->is_mock_location ?? false),

                'recorded_at' =>
                    $point->recorded_at
                    ?? $point->created_at,
            ];
        }

        return [
            'latitude' =>
                isset($point['latitude'])
                    ? (float) $point['latitude']
                    : null,

            'longitude' =>
                isset($point['longitude'])
                    ? (float) $point['longitude']
                    : null,

            'accuracy' =>
                isset($point['accuracy'])
                && $point['accuracy'] !== null
                    ? (float) $point['accuracy']
                    : null,

            'speed' =>
                isset($point['speed'])
                && $point['speed'] !== null
                    ? (float) $point['speed']
                    : null,

            'activity' =>
                $point['activity'] ?? null,

            'type' =>
                $point['type'] ?? null,

            'is_gps_on' =>
                (bool) ($point['is_gps_on'] ?? true),

            'is_mock_location' =>
                (bool) ($point['is_mock_location'] ?? false),

            'recorded_at' =>
                isset($point['recorded_at'])
                    ? Carbon::parse($point['recorded_at'])
                    : now(),
        ];
    }

    /**
     * Validate GPS coordinates boundaries.
     */
    private function hasValidCoordinates(
        ?float $latitude,
        ?float $longitude
    ): bool {
        return $latitude !== null
            &&
            $longitude !== null
            &&
            ! (
                $latitude === 0.0
                &&
                $longitude === 0.0
            )
            &&
            $latitude >= -90
            &&
            $latitude <= 90
            &&
            $longitude >= -180
            &&
            $longitude <= 180;
    }

    /**
     * Check duplicate coordinates up to 7 decimal precision.
     */
    private function sameCoordinates(
        array $previous,
        array $current
    ): bool {
        return round((float) $previous['latitude'], 7) === round((float) $current['latitude'], 7)
            && round((float) $previous['longitude'], 7) === round((float) $current['longitude'], 7);
    }

    /**
     * Detect STILL activity state.
     */
    private function isStillState(
        ?string $activity,
        ?string $type
    ): bool {
        return in_array(
            strtolower((string) $activity),
            [
                'activitytype.still',
                'still',
            ],
            true
        )
            || strtolower((string) $type) === 'still';
    }

    /**
     * Calculate time difference in seconds between two Carbon timestamps.
     */
    private function timeDifferenceSeconds(
        ?Carbon $previousTime,
        ?Carbon $currentTime
    ): int {
        if (! $previousTime || ! $currentTime) {
            return 0;
        }

        return $currentTime->getTimestamp() - $previousTime->getTimestamp();
    }

    /**
     * Return accepted response array.
     */
    private function accepted(
        array $metrics
    ): array {
        return [
            'accepted' => true,
            'reason' => null,
            ...$metrics,
        ];
    }

    /**
     * Return rejected response array.
     */
    private function rejected(
        string $reason,
        array $metrics = []
    ): array {
        return [
            'accepted' => false,
            'reason' => $reason,
            'distance_metres' => $metrics['distance_metres'] ?? null,
            'time_difference_seconds' => $metrics['time_difference_seconds'] ?? null,
            'speed_mps' => $metrics['speed_mps'] ?? null,
            'speed_kmph' => $metrics['speed_kmph'] ?? null,
            'bearing' => $metrics['bearing'] ?? null,
            'bearing_difference' => $metrics['bearing_difference'] ?? null,
        ];
    }

    /**
     * Load GPS settings from database app_settings table with caching.
     */
    private function databaseSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        try {
            if (! Schema::hasTable('app_settings')) {
                return self::$cachedSettings = [];
            }

            $values = AppSetting::query()
                ->whereIn(
                    'key',
                    [
                        'minimum_accuracy',
                        'minimum_distance_meters',
                        'maximum_speed_kmph',
                        'gps_max_accuracy_metres',
                        'gps_min_distance_metres',
                        'gps_max_speed_mps',
                        'gps_max_walking_speed_mps',
                        'gps_max_bearing_change_degrees',
                        'gps_bearing_min_segment_distance_metres',
                        'gps_bearing_min_distance_metres',
                        'tracking_interval_seconds',
                        'location_update_interval',
                        'location_update_interval_type',
                        'gps_max_inactive_gap_seconds',
                        'large_gap_minutes',
                        'large_gap_distance_meters',
                        'gps_max_jump_distance_metres',
                        'gps_douglas_peucker_tolerance_metres',
                        'timeline_max_accuracy_meters',
                        'timeline_minimum_distance_meters',
                        'timeline_max_computed_speed_kmh',
                        'timeline_max_bearing_change_degrees',
                        'mock_location_allowed',
                    ]
                )
                ->pluck(
                    'value',
                    'key'
                )
                ->all();

            $settings = [];

            foreach ($values as $key => $value) {
                if (str_starts_with($key, 'gps_')) {
                    $canonicalKey = $this->canonicalSettingKey($key);

                    if ($key === 'mock_location_allowed') {
                        $settings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        continue;
                    }

                    $settings[$canonicalKey] = (float) $value;
                }

                if ($key === 'tracking_interval_seconds') {
                    $settings[$key] = (float) $value;
                }

                if ($key === 'large_gap_distance_meters') {
                    $settings[$key] = (float) $value;
                }

                if ($key === 'large_gap_minutes') {
                    $settings['gps_max_inactive_gap_seconds'] = (float) $value * 60;
                }

                if ($key === 'maximum_speed_kmph') {
                    $settings['gps_max_speed_mps'] = (float) $value / 3.6;
                }

                if ($key === 'mock_location_allowed') {
                    $settings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
            }

            if (! array_key_exists('gps_max_jump_distance_metres', $settings)) {
                $settings['gps_max_jump_distance_metres'] = self::DEFAULT_MAX_JUMP_DISTANCE_METRES;
            }

            if (
                ! array_key_exists('tracking_interval_seconds', $settings)
                && isset($values['location_update_interval'])
            ) {
                $settings['tracking_interval_seconds'] = $this->secondsFrom(
                    (int) $values['location_update_interval'],
                    (string) ($values['location_update_interval_type'] ?? 'seconds')
                );
            }

            $fallbacks = [
                'timeline_max_accuracy_meters' => 'gps_max_accuracy_metres',
                'timeline_minimum_distance_meters' => 'gps_min_distance_metres',
                'minimum_accuracy' => 'gps_max_accuracy_metres',
                'minimum_distance_meters' => 'gps_min_distance_metres',
                'timeline_max_bearing_change_degrees' => 'gps_max_bearing_change_degrees',
                'timeline_max_computed_speed_kmh' => 'gps_max_speed_mps',
            ];

            foreach ($fallbacks as $source => $target) {
                if (
                    ! array_key_exists($target, $settings)
                    && array_key_exists($source, $values)
                ) {
                    $settings[$target] = $source === 'timeline_max_computed_speed_kmh'
                        ? (float) $values[$source] / 3.6
                        : (float) $values[$source];
                }
            }

            return self::$cachedSettings = $settings;

        } catch (\Throwable) {
            return self::$cachedSettings = [];
        }
    }

    /**
     * Convert legacy setting keys into canonical names.
     */
    private function canonicalSettingKey(string $key): string
    {
        return match ($key) {
            'timeline_max_accuracy_meters' => 'gps_max_accuracy_metres',
            'timeline_minimum_distance_meters' => 'gps_min_distance_metres',
            'minimum_accuracy' => 'gps_max_accuracy_metres',
            'minimum_distance_meters' => 'gps_min_distance_metres',
            'timeline_max_bearing_change_degrees' => 'gps_max_bearing_change_degrees',
            'gps_bearing_min_segment_distance_metres' => 'gps_bearing_min_distance_metres',
            default => $key,
        };
    }

    /**
     * Clear cached database settings.
     */
    public static function clearCachedSettings(): void
    {
        self::$cachedSettings = null;
    }

    /**
     * Convert interval values to seconds.
     */
    private function secondsFrom(int $value, string $type): int
    {
        return match ($type) {
            'minutes' => $value * 60,
            'hours' => $value * 3600,
            default => $value,
        };
    }
}