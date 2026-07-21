<?php

namespace App\Services;

use App\Models\LocationTracking;
use Illuminate\Support\Collection;

class TimelineGpsProcessor
{
    public const DEFAULT_MINIMUM_DISTANCE_METERS = 10.0;
    public const DEFAULT_MAX_ACCURACY_METERS = 20.0;
    public const DEFAULT_SIMPLIFY_AFTER_POINTS = 1000;
    public const DEFAULT_SIMPLIFICATION_TOLERANCE_METERS = 8.0;
    public const DEFAULT_BEARING_DRIFT_DISTANCE_METERS = 10.0;
    public const DEFAULT_BEARING_CHANGE_DEGREES = 60.0;
    public const DEFAULT_MAX_COMPUTED_SPEED_KMH = 80.0;

    public function filter(Collection $trackings, array $options = []): array
    {
        $minimumDistanceMeters = max(0.0, (float) ($options['minimum_distance_meters'] ?? self::DEFAULT_MINIMUM_DISTANCE_METERS));
        $maxAccuracyMeters = max(0.0, (float) ($options['max_accuracy_meters'] ?? self::DEFAULT_MAX_ACCURACY_METERS));
        $simplifyAfterPoints = max(0, (int) ($options['simplify_after_points'] ?? self::DEFAULT_SIMPLIFY_AFTER_POINTS));
        $simplificationToleranceMeters = max(0.0, (float) ($options['simplification_tolerance_meters'] ?? self::DEFAULT_SIMPLIFICATION_TOLERANCE_METERS));
        $bearingDriftDistanceMeters = max(0.0, (float) ($options['bearing_drift_distance_meters'] ?? self::DEFAULT_BEARING_DRIFT_DISTANCE_METERS));
        $bearingChangeDegrees = max(0.0, (float) ($options['bearing_change_degrees'] ?? self::DEFAULT_BEARING_CHANGE_DEGREES));
        $maxComputedSpeedKmh = max(0.0, (float) ($options['max_computed_speed_kmh'] ?? self::DEFAULT_MAX_COMPUTED_SPEED_KMH));

        $filtered = [];
        $seenTimestamps = [];
        $seenCoordinates = [];
        $lastBearing = null;

        $orderedTrackings = $this->ensureTimelineOrder($trackings);

        foreach ($orderedTrackings as $tracking) {
            if (! $this->hasValidCoordinates($tracking)) {
                continue;
            }

            if ($this->hasPoorAccuracy($tracking, $maxAccuracyMeters)) {
                continue;
            }

            $timestampKey = $this->timestampKey($tracking);
            if ($timestampKey !== null && isset($seenTimestamps[$timestampKey])) {
                continue;
            }

            $coordinateKey = $this->coordinateKey($tracking);
            if (isset($seenCoordinates[$coordinateKey])) {
                continue;
            }

            $previous = $filtered[count($filtered) - 1] ?? null;
            if ($previous) {
                $distanceMeters = $this->distanceMeters($previous, $tracking);

                if ($distanceMeters <= 0.0) {
                    continue;
                }

                if ($this->isUnrealisticJump($previous, $tracking, $distanceMeters, $maxComputedSpeedKmh)) {
                    continue;
                }

                if ((float) ($tracking->speed ?? 0) === 0.0 && $distanceMeters < $minimumDistanceMeters) {
                    continue;
                }

                $currentBearing = $this->movementBearingDegrees($previous, $tracking);
                if (
                    $lastBearing !== null
                    && $distanceMeters < $bearingDriftDistanceMeters
                    && $this->bearingDeltaDegrees($lastBearing, $currentBearing) >= $bearingChangeDegrees
                ) {
                    continue;
                }

                if ($distanceMeters < $minimumDistanceMeters) {
                    continue;
                }

                $lastBearing = $currentBearing;
            }

            $filtered[] = $tracking;
            $seenCoordinates[$coordinateKey] = true;
            if ($timestampKey !== null) {
                $seenTimestamps[$timestampKey] = true;
            }
        }

        if ($simplifyAfterPoints > 0 && count($filtered) > $simplifyAfterPoints) {
            $filtered = $this->simplify($filtered, $simplificationToleranceMeters);
        }

        return array_values($filtered);
    }

    public function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->haversineMeters($lat1, $lon1, $lat2, $lon2) / 1000;
    }

    private function hasValidCoordinates(LocationTracking $tracking): bool
    {
        if ($tracking->latitude === null || $tracking->longitude === null) {
            return false;
        }

        $latitude = (float) $tracking->latitude;
        $longitude = (float) $tracking->longitude;

        return $latitude !== 0.0
            && $longitude !== 0.0
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }

    private function ensureTimelineOrder(Collection $trackings): Collection
    {
        $previousTimestamp = null;
        $previousId = null;
        $isOrdered = true;

        foreach ($trackings as $tracking) {
            $timestamp = $tracking->created_at?->getTimestamp() ?? 0;
            $id = (int) ($tracking->id ?? 0);

            if (
                $previousTimestamp !== null
                && ($timestamp < $previousTimestamp || ($timestamp === $previousTimestamp && $id < $previousId))
            ) {
                $isOrdered = false;
                break;
            }

            $previousTimestamp = $timestamp;
            $previousId = $id;
        }

        if ($isOrdered) {
            return $trackings->values();
        }

        return $trackings
            ->sortBy([
                fn (LocationTracking $a, LocationTracking $b) => ($a->created_at?->getTimestamp() ?? 0) <=> ($b->created_at?->getTimestamp() ?? 0),
                fn (LocationTracking $a, LocationTracking $b) => ($a->id ?? 0) <=> ($b->id ?? 0),
            ])
            ->values();
    }

    private function hasPoorAccuracy(LocationTracking $tracking, float $maxAccuracyMeters): bool
    {
        return $tracking->accuracy !== null
            && $maxAccuracyMeters > 0
            && (float) $tracking->accuracy > $maxAccuracyMeters;
    }

    private function timestampKey(LocationTracking $tracking): ?string
    {
        $timestamp = $tracking->created_at ?? $tracking->recorded_at;

        return $timestamp ? (string) $timestamp->getTimestamp() : null;
    }

    private function coordinateKey(LocationTracking $tracking): string
    {
        return round((float) $tracking->latitude, 7) . ',' . round((float) $tracking->longitude, 7);
    }

    private function distanceMeters(LocationTracking $from, LocationTracking $to): float
    {
        return $this->haversineMeters(
            (float) $from->latitude,
            (float) $from->longitude,
            (float) $to->latitude,
            (float) $to->longitude
        );
    }

    private function isUnrealisticJump(LocationTracking $from, LocationTracking $to, float $distanceMeters, float $maxComputedSpeedKmh): bool
    {
        if ($maxComputedSpeedKmh <= 0 || ! $from->recorded_at || ! $to->recorded_at) {
            return false;
        }

        $seconds = max(1, abs($to->recorded_at->getTimestamp() - $from->recorded_at->getTimestamp()));
        $computedSpeedKmh = ($distanceMeters / 1000) / $seconds * 3600;

        return $computedSpeedKmh > $maxComputedSpeedKmh;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;
        $latDistance = deg2rad($lat2 - $lat1);
        $lonDistance = deg2rad($lon2 - $lon1);
        $a = sin($latDistance / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDistance / 2) ** 2;

        return $earthRadiusMeters * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function bearingDegrees(LocationTracking $from, LocationTracking $to): float
    {
        $lat1 = deg2rad((float) $from->latitude);
        $lat2 = deg2rad((float) $to->latitude);
        $lonDelta = deg2rad((float) $to->longitude - (float) $from->longitude);

        $y = sin($lonDelta) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lonDelta);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    private function movementBearingDegrees(LocationTracking $from, LocationTracking $to): float
    {
        if ($to->bearing !== null) {
            return fmod(((float) $to->bearing) + 360, 360);
        }

        return $this->bearingDegrees($from, $to);
    }

    private function bearingDeltaDegrees(float $a, float $b): float
    {
        $delta = abs($a - $b);

        return min($delta, 360 - $delta);
    }

    private function simplify(array $trackings, float $toleranceMeters): array
    {
        if (count($trackings) <= 2 || $toleranceMeters <= 0) {
            return $trackings;
        }

        $keepIndexes = $this->douglasPeuckerIndexes($trackings, 0, count($trackings) - 1, $toleranceMeters);
        sort($keepIndexes);

        return array_map(fn(int $index) => $trackings[$index], array_values(array_unique($keepIndexes)));
    }

    private function douglasPeuckerIndexes(array $trackings, int $start, int $end, float $toleranceMeters): array
    {
        if ($end <= $start + 1) {
            return [$start, $end];
        }

        $maxDistance = 0.0;
        $maxIndex = $start;

        for ($index = $start + 1; $index < $end; $index++) {
            $distance = $this->perpendicularDistanceMeters($trackings[$index], $trackings[$start], $trackings[$end]);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $index;
            }
        }

        if ($maxDistance <= $toleranceMeters) {
            return [$start, $end];
        }

        return array_merge(
            $this->douglasPeuckerIndexes($trackings, $start, $maxIndex, $toleranceMeters),
            $this->douglasPeuckerIndexes($trackings, $maxIndex, $end, $toleranceMeters)
        );
    }

    private function perpendicularDistanceMeters(LocationTracking $point, LocationTracking $start, LocationTracking $end): float
    {
        $latScale = 111320;
        $lngScale = 111320 * cos(deg2rad((float) $start->latitude));

        $x = ((float) $point->longitude - (float) $start->longitude) * $lngScale;
        $y = ((float) $point->latitude - (float) $start->latitude) * $latScale;
        $x2 = ((float) $end->longitude - (float) $start->longitude) * $lngScale;
        $y2 = ((float) $end->latitude - (float) $start->latitude) * $latScale;

        $lengthSquared = ($x2 * $x2) + ($y2 * $y2);
        if ($lengthSquared <= 0) {
            return sqrt(($x * $x) + ($y * $y));
        }

        $projection = max(0, min(1, (($x * $x2) + ($y * $y2)) / $lengthSquared));
        $projectedX = $projection * $x2;
        $projectedY = $projection * $y2;

        return sqrt((($x - $projectedX) ** 2) + (($y - $projectedY) ** 2));
    }
}
