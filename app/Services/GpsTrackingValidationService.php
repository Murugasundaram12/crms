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
    | Default GPS Validation Settings
    |--------------------------------------------------------------------------
    */

    public const DEFAULT_MAX_ACCURACY_METRES = 30.0;

    public const DEFAULT_MIN_DISTANCE_METRES = 5.0;

    public const DEFAULT_MAX_SPEED_MPS = 33.33333333; // 120 km/h

    public const DEFAULT_MAX_WALKING_SPEED_MPS = 5.0;

    public const DEFAULT_MAX_BEARING_CHANGE_DEGREES = 120.0;

    public const DEFAULT_BEARING_MIN_DISTANCE_METRES = 10.0;

    public const DEFAULT_TRACKING_INTERVAL_SECONDS = 30;

    public const DEFAULT_MAX_INACTIVE_GAP_SECONDS = 600;

    public const DEFAULT_DOUGLAS_PEUCKER_TOLERANCE_METRES = 15.0;

    /*
    |--------------------------------------------------------------------------
    | GPS Jump Protection
    |--------------------------------------------------------------------------
    |
    | This protects against sudden GPS jumps such as:
    |
    | Road
    |   |
    |   |-------> Building / side road GPS point
    |   |
    | Road
    |
    */

    public const DEFAULT_MAX_JUMP_DISTANCE_METRES = 100.0;

    /*
    |--------------------------------------------------------------------------
    | Stationary GPS protection
    |--------------------------------------------------------------------------
    */

    public const DEFAULT_STATIONARY_SPEED_MPS = 0.5;

    public const DEFAULT_STATIONARY_DISTANCE_METRES = 5.0;

    private static ?array $cachedSettings = null;

    /**
     * Get all GPS settings.
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

            /*
             * New protection against sudden GPS jumps.
             */
            'gps_max_jump_distance_metres'
                => self::DEFAULT_MAX_JUMP_DISTANCE_METRES,

            /*
             * Mock locations are rejected by default.
             */
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
        | 1. Current point
        |--------------------------------------------------------------------------
        */

        $currentPoint = $this->pointData($current);

        /*
        |--------------------------------------------------------------------------
        | 2. Validate coordinates
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
        | 5. GLOBAL accuracy check
        |--------------------------------------------------------------------------
        |
        | Accuracy > 30m is ALWAYS rejected.
        |
        | This applies even to the first GPS point.
        |
        */

        $globalMaxAccuracy = self::DEFAULT_MAX_ACCURACY_METRES;

        if (
            $currentPoint['accuracy'] === null
            || (float) $currentPoint['accuracy'] <= 0
            || (float) $currentPoint['accuracy'] > $globalMaxAccuracy
        ) {
            return $this->rejected('accuracy_exceeded');
        }

        /*
        |--------------------------------------------------------------------------
        | 6. First valid GPS point
        |--------------------------------------------------------------------------
        |
        | No previous point means distance/speed cannot be calculated.
        |
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
        | 7. Previous point
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
        | 10. Distance calculation
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
        |
        | IMPORTANT:
        |
        | Device reported speed is NOT trusted for movement classification.
        |
        | Speed is calculated from:
        |
        | distance / time
        |
        */

        $speedMps = $distanceMetres / $timeDifferenceSeconds;

        $speedKmph = $speedMps * 3.6;

        $maxSpeedMps = (float) (
            $settings['gps_max_speed_mps']
            ?? self::DEFAULT_MAX_SPEED_MPS
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Maximum speed protection
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
        | 13. GPS JUMP PROTECTION
        |--------------------------------------------------------------------------
        |
        | This is the important new protection.
        |
        | Example:
        |
        | Previous:
        | Road = 13.1234, 80.1234
        |
        | Current:
        | Building = 13.1250, 80.1270
        |
        | If distance is abnormally large, reject it.
        |
        */

        $configuredMaxJumpDistance = (float) (
            $settings['gps_max_jump_distance_metres']
            ?? self::DEFAULT_MAX_JUMP_DISTANCE_METRES
        );

        /*
         * Dynamic maximum allowed distance.
         *
         * We don't blindly use 100m because a vehicle can legitimately
         * travel more than 100m during a longer GPS interval.
         *
         * Formula:
         *
         * maximum speed × elapsed time × safety factor
         *
         * Minimum protection = 100m.
         */

        $dynamicMaxJumpDistance = max(
            $configuredMaxJumpDistance,
            $maxSpeedMps
                * $timeDifferenceSeconds
                * 1.5
        );

        /*
         * Prevent an excessively large dynamic threshold.
         *
         * Even if there is a very large timestamp gap, we don't want
         * one GPS point to create a giant polyline jump.
         */

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
        | 15. Previous bearing validation
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

            /*
             * Only calculate bearing change when both segments
             * have enough distance.
             *
             * This avoids random bearing changes while standing still.
             */

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
        | 16. Metrics
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

        /*
        |--------------------------------------------------------------------------
        | 17. Movement classification
        |--------------------------------------------------------------------------
        |
        | Backend calculated speed is used.
        |
        */

        $evalSpeed = $speedMps;

        $accuracy = (float) $currentPoint['accuracy'];

        /*
        |--------------------------------------------------------------------------
        | 18. Stationary / very slow movement
        |--------------------------------------------------------------------------
        |
        | < 0.5 m/s
        |
        | Don't save repeated stationary GPS points.
        |
        */

        if ($evalSpeed < self::DEFAULT_STATIONARY_SPEED_MPS) {
            return $this->rejected(
                'speed_below_threshold',
                $metrics
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 19. Walking / slow movement
        |--------------------------------------------------------------------------
        |
        | 0.5 <= speed < 5 m/s
        |
        | Accuracy <= 20m
        | Distance >= 5m
        |
        */

        if (
            $evalSpeed >= self::DEFAULT_STATIONARY_SPEED_MPS
            &&
            $evalSpeed < self::DEFAULT_MAX_WALKING_SPEED_MPS
        ) {
            /*
             * Walking accuracy requirement.
             */

            if ($accuracy > 20.0) {
                return $this->rejected(
                    'accuracy_exceeded',
                    $metrics
                );
            }

            /*
             * Walking minimum movement.
             */

            if ($distanceMetres < 5.0) {
                return $this->rejected(
                    'distance_below_threshold',
                    $metrics
                );
            }

            return $this->accepted($metrics);
        }

        /*
        |--------------------------------------------------------------------------
        | 20. Vehicle movement
        |--------------------------------------------------------------------------
        |
        | speed >= 5 m/s
        |
        | Accuracy <= 25m
        | Distance >= 10m
        | Bearing change <= 120 degrees
        |
        */

        if ($evalSpeed >= self::DEFAULT_MAX_WALKING_SPEED_MPS) {
            /*
             * Vehicle accuracy.
             */

            if ($accuracy > 25.0) {
                return $this->rejected(
                    'accuracy_exceeded',
                    $metrics
                );
            }

            /*
             * Vehicle minimum movement.
             */

            if ($distanceMetres < 10.0) {
                return $this->rejected(
                    'distance_below_threshold',
                    $metrics
                );
            }

            /*
             * Vehicle bearing jump.
             */

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
        | 21. Fallback
        |--------------------------------------------------------------------------
        */

        return $this->rejected(
            'invalid_movement',
            $metrics
        );
    }

    /**
     * Validate standalone GPS quality.
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
     * Calculate distance between two GPS coordinates.
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
     * Calculate bearing between two GPS coordinates.
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
     * Calculate smallest bearing difference.
     */
    public function bearingDifferenceDegrees(
        float $previousBearing,
        float $currentBearing
    ): float {
        $difference = abs(
            $currentBearing - $previousBearing
        );

        return min(
            $difference,
            360 - $difference
        );
    }

    /**
     * Normalize GPS point data.
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
     * Validate GPS coordinates.
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
     * Check duplicate coordinates.
     */
    private function sameCoordinates(
        array $previous,
        array $current
    ): bool {
        return round(
            (float) $previous['latitude'],
            7
        )
            ===
            round(
                (float) $current['latitude'],
                7
            )
            &&
            round(
                (float) $previous['longitude'],
                7
            )
            ===
            round(
                (float) $current['longitude'],
                7
            );
    }

    /**
     * Detect STILL activity.
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
            ||
            strtolower((string) $type) === 'still';
    }

    /**
     * Calculate time difference.
     */
    private function timeDifferenceSeconds(
        ?Carbon $previousTime,
        ?Carbon $currentTime
    ): int {
        if (
            ! $previousTime
            ||
            ! $currentTime
        ) {
            return 0;
        }

        return
            $currentTime->getTimestamp()
            -
            $previousTime->getTimestamp();
    }

    /**
     * Accepted response.
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
     * Rejected response.
     */
    private function rejected(
        string $reason,
        array $metrics = []
    ): array {
        return [
            'accepted' => false,

            'reason' => $reason,

            'distance_metres' =>
                $metrics['distance_metres'] ?? null,

            'time_difference_seconds' =>
                $metrics['time_difference_seconds'] ?? null,

            'speed_mps' =>
                $metrics['speed_mps'] ?? null,

            'speed_kmph' =>
                $metrics['speed_kmph'] ?? null,

            'bearing' =>
                $metrics['bearing'] ?? null,

            'bearing_difference' =>
                $metrics['bearing_difference'] ?? null,
        ];
    }

    /**
     * Load GPS settings from database.
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

                /*
                 * GPS settings.
                 */

                if (str_starts_with($key, 'gps_')) {
                    $canonicalKey =
                        $this->canonicalSettingKey($key);

                    /*
                     * Boolean setting.
                     */

                    if ($key === 'mock_location_allowed') {
                        $settings[$key] =
                            filter_var(
                                $value,
                                FILTER_VALIDATE_BOOLEAN
                            );

                        continue;
                    }

                    /*
                     * Numeric GPS setting.
                     */

                    $settings[$canonicalKey] =
                        (float) $value;
                }

                /*
                 * Tracking interval.
                 */

                if (
                    $key === 'tracking_interval_seconds'
                ) {
                    $settings[$key] =
                        (float) $value;
                }

                /*
                 * Large gap distance.
                 */

                if (
                    $key === 'large_gap_distance_meters'
                ) {
                    $settings[$key] =
                        (float) $value;
                }

                /*
                 * Large gap minutes -> seconds.
                 */

                if (
                    $key === 'large_gap_minutes'
                ) {
                    $settings[
                        'gps_max_inactive_gap_seconds'
                    ] =
                        (float) $value * 60;
                }

                /*
                 * Maximum speed stored in km/h.
                 */

                if (
                    $key === 'maximum_speed_kmph'
                ) {
                    $settings[
                        'gps_max_speed_mps'
                    ] =
                        (float) $value / 3.6;
                }

                /*
                 * Mock location.
                 */

                if (
                    $key === 'mock_location_allowed'
                ) {
                    $settings[$key] =
                        filter_var(
                            $value,
                            FILTER_VALIDATE_BOOLEAN
                        );
                }
            }

            /*
             * New jump-distance setting.
             *
             * If not present in DB, default value from settings()
             * will be used.
             */

            if (
                ! array_key_exists(
                    'gps_max_jump_distance_metres',
                    $settings
                )
            ) {
                $settings[
                    'gps_max_jump_distance_metres'
                ] =
                    self::DEFAULT_MAX_JUMP_DISTANCE_METRES;
            }

            /*
             * Tracking interval fallback.
             */

            if (
                ! array_key_exists(
                    'tracking_interval_seconds',
                    $settings
                )
                &&
                isset(
                    $values['location_update_interval']
                )
            ) {
                $settings[
                    'tracking_interval_seconds'
                ] =
                    $this->secondsFrom(
                        (int) $values[
                            'location_update_interval'
                        ],
                        (string) (
                            $values[
                                'location_update_interval_type'
                            ]
                            ?? 'seconds'
                        )
                    );
            }

            /*
             * Legacy setting fallbacks.
             */

            $fallbacks = [
                'timeline_max_accuracy_meters'
                    => 'gps_max_accuracy_metres',

                'timeline_minimum_distance_meters'
                    => 'gps_min_distance_metres',

                'minimum_accuracy'
                    => 'gps_max_accuracy_metres',

                'minimum_distance_meters'
                    => 'gps_min_distance_metres',

                'timeline_max_bearing_change_degrees'
                    => 'gps_max_bearing_change_degrees',

                'timeline_max_computed_speed_kmh'
                    => 'gps_max_speed_mps',
            ];

            foreach (
                $fallbacks as $source => $target
            ) {
                if (
                    ! array_key_exists(
                        $target,
                        $settings
                    )
                    &&
                    array_key_exists(
                        $source,
                        $values
                    )
                ) {
                    $settings[$target] =
                        $source
                        ===
                        'timeline_max_computed_speed_kmh'
                            ? (float) $values[$source] / 3.6
                            : (float) $values[$source];
                }
            }

            return self::$cachedSettings =
                $settings;

        } catch (\Throwable) {

            return self::$cachedSettings = [];
        }
    }

    /**
     * Convert legacy setting names into canonical names.
     */
    private function canonicalSettingKey(
        string $key
    ): string {
        return match ($key) {

            'timeline_max_accuracy_meters'
                => 'gps_max_accuracy_metres',

            'timeline_minimum_distance_meters'
                => 'gps_min_distance_metres',

            'minimum_accuracy'
                => 'gps_max_accuracy_metres',

            'minimum_distance_meters'
                => 'gps_min_distance_metres',

            'timeline_max_bearing_change_degrees'
                => 'gps_max_bearing_change_degrees',

            'gps_bearing_min_segment_distance_metres'
                => 'gps_bearing_min_distance_metres',

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
     * Convert interval to seconds.
     */
    private function secondsFrom(
        int $value,
        string $type
    ): int {
        return match ($type) {

            'minutes'
                => $value * 60,

            'hours'
                => $value * 3600,

            default
                => $value,
        };
    }
}