<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LocationTracking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class GpsTrackingValidationService
{
    public const DEFAULT_MAX_ACCURACY_METRES = 8.0;
    public const DEFAULT_MIN_DISTANCE_METRES = 5.0;
    public const DEFAULT_MAX_SPEED_MPS = 25.0;
    public const DEFAULT_MAX_BEARING_CHANGE_DEGREES = 45.0;
    public const DEFAULT_BEARING_MIN_DISTANCE_METRES = 10.0;
    public const DEFAULT_MAX_INACTIVE_GAP_SECONDS = 600;
    public const DEFAULT_DOUGLAS_PEUCKER_TOLERANCE_METRES = 15.0;

    private static ?array $cachedSettings = null;

    public function settings(array $overrides = []): array
    {
        $settings = [
            'gps_max_accuracy_metres' => self::DEFAULT_MAX_ACCURACY_METRES,
            'gps_min_distance_metres' => self::DEFAULT_MIN_DISTANCE_METRES,
            'gps_max_speed_mps' => self::DEFAULT_MAX_SPEED_MPS,
            'gps_max_bearing_change_degrees' => self::DEFAULT_MAX_BEARING_CHANGE_DEGREES,
            'gps_bearing_min_distance_metres' => self::DEFAULT_BEARING_MIN_DISTANCE_METRES,
            'gps_max_inactive_gap_seconds' => self::DEFAULT_MAX_INACTIVE_GAP_SECONDS,
            'gps_douglas_peucker_tolerance_metres' => self::DEFAULT_DOUGLAS_PEUCKER_TOLERANCE_METRES,
        ];

        return [
            ...$settings,
            ...$this->databaseSettings(),
            ...array_filter($overrides, fn ($value) => $value !== null),
        ];
    }

    public function validate(array|LocationTracking $current, ?LocationTracking $previous = null, ?LocationTracking $previousPrevious = null, array $overrides = []): array
    {
        return $this->validateWithSettings($current, $previous, $previousPrevious, $this->settings($overrides));
    }

    public function validateWithSettings(array|LocationTracking $current, ?LocationTracking $previous, ?LocationTracking $previousPrevious, array $settings): array
    {
        $currentPoint = $this->pointData($current);

        if (! $this->hasValidCoordinates($currentPoint['latitude'], $currentPoint['longitude'])) {
            return $this->rejected('invalid_coordinates');
        }

        if ((bool) ($currentPoint['is_mock_location'] ?? false)) {
            return $this->rejected('invalid_coordinates');
        }

        if ($currentPoint['accuracy'] === null
            || (float) $currentPoint['accuracy'] <= 0
            || (float) $currentPoint['accuracy'] > (float) $settings['gps_max_accuracy_metres']) {
            return $this->rejected('accuracy_exceeded');
        }

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

        $previousPoint = $this->pointData($previous);

        if ($this->sameCoordinates($previousPoint, $currentPoint)) {
            return $this->rejected('duplicate_location');
        }

        $timeDifferenceSeconds = $this->timeDifferenceSeconds($previousPoint['recorded_at'], $currentPoint['recorded_at']);
        if ($timeDifferenceSeconds <= 0) {
            return $this->rejected('invalid_timestamp', [
                'time_difference_seconds' => $timeDifferenceSeconds,
            ]);
        }

        $distanceMetres = $this->distanceMetres(
            (float) $previousPoint['latitude'],
            (float) $previousPoint['longitude'],
            (float) $currentPoint['latitude'],
            (float) $currentPoint['longitude'],
        );

        if ($distanceMetres <= (float) $settings['gps_min_distance_metres']) {
            return $this->rejected('distance_below_threshold', [
                'distance_metres' => $distanceMetres,
                'time_difference_seconds' => $timeDifferenceSeconds,
            ]);
        }

        if ($this->isStillState($currentPoint['activity'] ?? null, $currentPoint['type'] ?? null)
            && ($currentPoint['speed'] === null || (float) $currentPoint['speed'] < 0.8)
            && $distanceMetres <= max(5.0, (float) $settings['gps_min_distance_metres'])) {
            return $this->rejected('distance_below_threshold', [
                'distance_metres' => $distanceMetres,
                'time_difference_seconds' => $timeDifferenceSeconds,
            ]);
        }

        $speedMps = $distanceMetres / $timeDifferenceSeconds;
        $speedKmph = $speedMps * 3.6;
        $maxSpeedMps = (float) $settings['gps_max_speed_mps'];

        if ($speedMps > $maxSpeedMps || ($currentPoint['speed'] !== null && (float) $currentPoint['speed'] > $maxSpeedMps)) {
            return $this->rejected('speed_exceeded', [
                'distance_metres' => $distanceMetres,
                'time_difference_seconds' => $timeDifferenceSeconds,
                'speed_mps' => $speedMps,
                'speed_kmph' => $speedKmph,
            ]);
        }

        $bearing = $this->bearingDegrees(
            (float) $previousPoint['latitude'],
            (float) $previousPoint['longitude'],
            (float) $currentPoint['latitude'],
            (float) $currentPoint['longitude'],
        );
        $bearingDifference = null;

        if ($previousPrevious) {
            $previousPreviousPoint = $this->pointData($previousPrevious);
            $previousSegmentDistance = $this->distanceMetres(
                (float) $previousPreviousPoint['latitude'],
                (float) $previousPreviousPoint['longitude'],
                (float) $previousPoint['latitude'],
                (float) $previousPoint['longitude'],
            );

            if ($previousSegmentDistance >= (float) $settings['gps_bearing_min_distance_metres']
                && $distanceMetres >= (float) $settings['gps_bearing_min_distance_metres']) {
                $previousBearing = $this->bearingDegrees(
                    (float) $previousPreviousPoint['latitude'],
                    (float) $previousPreviousPoint['longitude'],
                    (float) $previousPoint['latitude'],
                    (float) $previousPoint['longitude'],
                );
                $bearingDifference = $this->bearingDifferenceDegrees($previousBearing, $bearing);

            }
        }

        return $this->accepted([
            'distance_metres' => $distanceMetres,
            'time_difference_seconds' => $timeDifferenceSeconds,
            'speed_mps' => $speedMps,
            'speed_kmph' => $speedKmph,
            'bearing' => $bearing,
            'bearing_difference' => $bearingDifference,
        ]);
    }

    public function hasStandaloneQuality(array|LocationTracking $point, array $overrides = []): bool
    {
        $result = $this->validate($point, null, null, $overrides);

        return (bool) $result['accepted'];
    }

    public function distanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMetres = 6371000;
        $latDistance = deg2rad($lat2 - $lat1);
        $lngDistance = deg2rad($lng2 - $lng1);
        $a = sin($latDistance / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDistance / 2) ** 2;

        return $earthRadiusMetres * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function bearingDegrees(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $fromLat = deg2rad($lat1);
        $toLat = deg2rad($lat2);
        $lngDelta = deg2rad($lng2 - $lng1);

        $y = sin($lngDelta) * cos($toLat);
        $x = cos($fromLat) * sin($toLat) - sin($fromLat) * cos($toLat) * cos($lngDelta);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    public function bearingDifferenceDegrees(float $previousBearing, float $currentBearing): float
    {
        $difference = abs($currentBearing - $previousBearing);

        return min($difference, 360 - $difference);
    }

    private function pointData(array|LocationTracking $point): array
    {
        if ($point instanceof LocationTracking) {
            return [
                'latitude' => $point->latitude !== null ? (float) $point->latitude : null,
                'longitude' => $point->longitude !== null ? (float) $point->longitude : null,
                'accuracy' => $point->accuracy !== null ? (float) $point->accuracy : null,
                'speed' => $point->speed !== null ? (float) $point->speed : null,
                'activity' => $point->activity,
                'type' => $point->type,
                'is_mock_location' => (bool) ($point->is_mock_location ?? false),
                'recorded_at' => $point->recorded_at ?? $point->created_at,
            ];
        }

        return [
            'latitude' => isset($point['latitude']) ? (float) $point['latitude'] : null,
            'longitude' => isset($point['longitude']) ? (float) $point['longitude'] : null,
            'accuracy' => isset($point['accuracy']) && $point['accuracy'] !== null ? (float) $point['accuracy'] : null,
            'speed' => isset($point['speed']) && $point['speed'] !== null ? (float) $point['speed'] : null,
            'activity' => $point['activity'] ?? null,
            'type' => $point['type'] ?? null,
            'is_mock_location' => (bool) ($point['is_mock_location'] ?? false),
            'recorded_at' => isset($point['recorded_at']) ? Carbon::parse($point['recorded_at']) : now(),
        ];
    }

    private function hasValidCoordinates(?float $latitude, ?float $longitude): bool
    {
        return $latitude !== null
            && $longitude !== null
            && ! ($latitude === 0.0 && $longitude === 0.0)
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }

    private function sameCoordinates(array $previous, array $current): bool
    {
        return round((float) $previous['latitude'], 7) === round((float) $current['latitude'], 7)
            && round((float) $previous['longitude'], 7) === round((float) $current['longitude'], 7);
    }

    private function isStillState(?string $activity, ?string $type): bool
    {
        return in_array(strtolower((string) $activity), ['activitytype.still', 'still'], true)
            || strtolower((string) $type) === 'still';
    }

    private function timeDifferenceSeconds(?Carbon $previousTime, ?Carbon $currentTime): int
    {
        if (! $previousTime || ! $currentTime) {
            return 0;
        }

        return $currentTime->getTimestamp() - $previousTime->getTimestamp();
    }

    private function accepted(array $metrics): array
    {
        return [
            'accepted' => true,
            'reason' => null,
            ...$metrics,
        ];
    }

    private function rejected(string $reason, array $metrics = []): array
    {
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
                ->whereIn('key', [
                    'gps_max_accuracy_metres',
                    'gps_min_distance_metres',
                    'gps_max_speed_mps',
                    'gps_max_bearing_change_degrees',
                    'gps_bearing_min_segment_distance_metres',
                    'gps_bearing_min_distance_metres',
                    'gps_max_inactive_gap_seconds',
                    'gps_douglas_peucker_tolerance_metres',
                    'timeline_max_accuracy_meters',
                    'timeline_minimum_distance_meters',
                    'timeline_max_computed_speed_kmh',
                    'timeline_max_bearing_change_degrees',
                ])
                ->pluck('value', 'key')
                ->all();

            $settings = [];
            foreach ($values as $key => $value) {
                if (str_starts_with($key, 'gps_')) {
                    $settings[$this->canonicalSettingKey($key)] = (float) $value;
                }
            }

            $fallbacks = [
                'timeline_max_accuracy_meters' => 'gps_max_accuracy_metres',
                'timeline_minimum_distance_meters' => 'gps_min_distance_metres',
                'timeline_max_bearing_change_degrees' => 'gps_max_bearing_change_degrees',
                'timeline_max_computed_speed_kmh' => 'gps_max_speed_mps',
            ];

            foreach ($fallbacks as $source => $target) {
                if (! array_key_exists($target, $settings) && array_key_exists($source, $values)) {
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

    private function canonicalSettingKey(string $key): string
    {
        return match ($key) {
            'timeline_max_accuracy_meters' => 'gps_max_accuracy_metres',
            'timeline_minimum_distance_meters' => 'gps_min_distance_metres',
            'timeline_max_bearing_change_degrees' => 'gps_max_bearing_change_degrees',
            'gps_bearing_min_segment_distance_metres' => 'gps_bearing_min_distance_metres',
            default => $key,
        };
    }
}
