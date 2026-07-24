<?php

namespace App\Services;

use App\Models\LocationTracking;
use Illuminate\Support\Collection;

class TimelineGpsProcessor
{
    public const DEFAULT_MINIMUM_DISTANCE_METERS = 3.0;
    public const DEFAULT_MAX_ACCURACY_METERS = 10.0;
    public const DEFAULT_SIMPLIFY_AFTER_POINTS = 1000;
    public const DEFAULT_SIMPLIFICATION_TOLERANCE_METERS = 8.0;
    public const DEFAULT_BEARING_DRIFT_DISTANCE_METERS = 10.0;
    public const DEFAULT_BEARING_CHANGE_DEGREES = 60.0;
    public const DEFAULT_MAX_BEARING_CHANGE_DEGREES = 170.0;
    public const DEFAULT_MAX_COMPUTED_SPEED_KMH = 90.0;

    public function filter(Collection $trackings, array $options = []): array
    {
        $minimumDistanceMeters = max(0.0, (float) ($options['minimum_distance_meters'] ?? self::DEFAULT_MINIMUM_DISTANCE_METERS));
        $maxAccuracyMeters = max(0.0, (float) ($options['max_accuracy_meters'] ?? self::DEFAULT_MAX_ACCURACY_METERS));
        $simplifyAfterPoints = max(0, (int) ($options['simplify_after_points'] ?? self::DEFAULT_SIMPLIFY_AFTER_POINTS));
        $simplificationToleranceMeters = max(0.0, (float) ($options['simplification_tolerance_meters'] ?? self::DEFAULT_SIMPLIFICATION_TOLERANCE_METERS));
        $bearingDriftDistanceMeters = max(0.0, (float) ($options['bearing_drift_distance_meters'] ?? self::DEFAULT_BEARING_DRIFT_DISTANCE_METERS));
        $maxBearingChangeDegrees = max(0.0, (float) ($options['max_bearing_change_degrees'] ?? self::DEFAULT_MAX_BEARING_CHANGE_DEGREES));
        $maxComputedSpeedKmh = max(0.0, (float) ($options['max_computed_speed_kmh'] ?? self::DEFAULT_MAX_COMPUTED_SPEED_KMH));
        $validator = app(GpsTrackingValidationService::class);
        $validatorOptions = [
            'gps_min_distance_metres' => $minimumDistanceMeters,
            'gps_max_accuracy_metres' => $maxAccuracyMeters,
            'gps_max_speed_mps' => $maxComputedSpeedKmh / 3.6,
            'gps_max_bearing_change_degrees' => $maxBearingChangeDegrees,
            'gps_bearing_min_distance_metres' => $bearingDriftDistanceMeters,
        ];
        $validatorSettings = $validator->settings($validatorOptions);

        $filtered = [];
        $seenTimestamps = [];
        $seenCoordinates = [];

        $orderedTrackings = $this->ensureTimelineOrder($trackings);

        foreach ($orderedTrackings as $tracking) {
            $timestampKey = $this->timestampKey($tracking);
            if ($timestampKey !== null && isset($seenTimestamps[$timestampKey])) {
                continue;
            }

            $previousCoordinateKey = isset($filtered[count($filtered) - 1]) ? $this->coordinateKey($filtered[count($filtered) - 1]) : null;
            $coordinateKey = $this->coordinateKey($tracking);
            if ($previousCoordinateKey && $previousCoordinateKey === $coordinateKey) {
                continue;
            }

            $previous = $filtered[count($filtered) - 1] ?? null;
            $previousPrevious = $filtered[count($filtered) - 2] ?? null;
            $result = $validator->validateWithSettings($tracking, $previous, $previousPrevious, $validatorSettings);

            if (! $result['accepted']) {
                if ($this->shouldKeepTimelineMarker($tracking, $result)) {
                    $filtered[] = $tracking;
                    $seenCoordinates[$coordinateKey] = true;
                    if ($timestampKey !== null) {
                        $seenTimestamps[$timestampKey] = true;
                    }
                }

                continue;
            }

            $filtered[] = $tracking;
            $seenCoordinates[$coordinateKey] = true;
            if ($timestampKey !== null) {
                $seenTimestamps[$timestampKey] = true;
            }
        }

        $filtered = $this->collapseStationaryDriftCluster($filtered);

        if ($simplifyAfterPoints > 0 && count($filtered) > $simplifyAfterPoints) {
            $filtered = $this->simplify($filtered, $simplificationToleranceMeters);
        }

        return array_values($filtered);
    }

    public function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->haversineMeters($lat1, $lon1, $lat2, $lon2) / 1000;
    }

    private function ensureTimelineOrder(Collection $trackings): Collection
    {
        $previousTimestamp = null;
        $previousId = null;
        $isOrdered = true;

        foreach ($trackings as $tracking) {
            $timestamp = $this->trackingTimestamp($tracking);
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
                fn (LocationTracking $a, LocationTracking $b) => $this->trackingTimestamp($a) <=> $this->trackingTimestamp($b),
                fn (LocationTracking $a, LocationTracking $b) => ($a->id ?? 0) <=> ($b->id ?? 0),
            ])
            ->values();
    }

    private function shouldKeepTimelineMarker(LocationTracking $tracking, array $validationResult): bool
    {
        $type = strtolower((string) $tracking->type);
        $activity = strtolower((string) $tracking->activity);
        if (in_array($type, ['checked_in', 'checked_out', 'still', 'proof_post'], true)) {
            return true;
        }

        return in_array($activity, ['activitytype.still', 'still'], true);
    }

    private function trackingTimestamp(LocationTracking $tracking): int
    {
        return ($tracking->recorded_at ?? $tracking->created_at)?->getTimestamp() ?? 0;
    }

    private function timestampKey(LocationTracking $tracking): ?string
    {
        $timestamp = $tracking->recorded_at ?? $tracking->created_at;

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

    private function collapseStationaryDriftCluster(array $trackings): array
    {
        if (count($trackings) < 4) {
            return $trackings;
        }

        $pathLengthMeters = 0.0;
        for ($index = 1; $index < count($trackings); $index++) {
            $pathLengthMeters += $this->distanceMeters($trackings[$index - 1], $trackings[$index]);
        }

        $directDistanceMeters = $this->distanceMeters($trackings[0], $trackings[count($trackings) - 1]);
        $detourRatio = $pathLengthMeters / max(1.0, $directDistanceMeters);

        if ($pathLengthMeters < 200 || $directDistanceMeters > 120 || $detourRatio < 4) {
            return $trackings;
        }

        $center = $this->averageCoordinate($trackings);
        $maxRadiusMeters = 0.0;
        foreach ($trackings as $tracking) {
            $maxRadiusMeters = max(
                $maxRadiusMeters,
                $this->haversineMeters($center['lat'], $center['lng'], (float) $tracking->latitude, (float) $tracking->longitude)
            );
        }

        if ($maxRadiusMeters > 120 || $this->medianSpeedMetersPerSecond($trackings) > 1.5) {
            return $trackings;
        }

        return [$trackings[0]];
    }

    private function averageCoordinate(array $trackings): array
    {
        $lat = 0.0;
        $lng = 0.0;

        foreach ($trackings as $tracking) {
            $lat += (float) $tracking->latitude;
            $lng += (float) $tracking->longitude;
        }

        return [
            'lat' => $lat / count($trackings),
            'lng' => $lng / count($trackings),
        ];
    }

    private function medianSpeedMetersPerSecond(array $trackings): float
    {
        $speeds = array_values(array_filter(
            array_map(fn (LocationTracking $tracking) => $tracking->speed !== null ? (float) $tracking->speed : null, $trackings),
            fn (?float $speed) => $speed !== null
        ));

        if ($speeds === []) {
            return 0.0;
        }

        sort($speeds);
        $middle = intdiv(count($speeds), 2);

        if (count($speeds) % 2 === 1) {
            return $speeds[$middle];
        }

        return ($speeds[$middle - 1] + $speeds[$middle]) / 2;
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

    private function simplify(array $trackings, float $toleranceMeters): array
    {
        if (count($trackings) <= 2 || $toleranceMeters <= 0) {
            return $trackings;
        }

        $keepIndexes = $this->douglasPeuckerIndexes($trackings, $toleranceMeters);
        sort($keepIndexes);

        return array_map(fn(int $index) => $trackings[$index], array_values(array_unique($keepIndexes)));
    }

    private function douglasPeuckerIndexes(array $trackings, float $toleranceMeters): array
    {
        $lastIndex = count($trackings) - 1;
        $keepIndexes = [0 => true, $lastIndex => true];
        $stack = [[0, $lastIndex]];

        while ($range = array_pop($stack)) {
            [$start, $end] = $range;

            if ($end <= $start + 1) {
                continue;
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
                continue;
            }

            $keepIndexes[$maxIndex] = true;
            $stack[] = [$start, $maxIndex];
            $stack[] = [$maxIndex, $end];
        }

        return array_keys($keepIndexes);
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
