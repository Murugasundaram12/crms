<?php

namespace App\Services;

use App\Models\LocationTracking;
use Illuminate\Support\Collection;

class EmployeeTimelineBuilder
{
    public function __construct(
        private readonly GpsTrackingValidationService $gpsValidator,
    ) {
    }

    public function build(Collection $rawTrackings, array $options = []): array
    {
        $settings = $this->gpsValidator->settings([
            'gps_max_accuracy_metres' => $options['max_accuracy_meters'] ?? null,
            'gps_min_distance_metres' => $options['minimum_distance_meters'] ?? null,
            'gps_max_speed_mps' => isset($options['max_computed_speed_kmh']) ? (float) $options['max_computed_speed_kmh'] / 3.6 : null,
            'gps_max_bearing_change_degrees' => $options['max_bearing_change_degrees'] ?? null,
            'gps_bearing_min_distance_metres' => $options['bearing_drift_distance_meters'] ?? null,
            'gps_douglas_peucker_tolerance_metres' => $options['douglas_peucker_tolerance_meters'] ?? null,
        ]);

        $items = collect();
        $segments = [];
        $currentSegment = null;
        $acceptedMovement = [];
        $diagnostics = [];
        $reasons = [];
        $segmentNumber = 0;
        $seenTimestamps = [];
        $seenCoordinates = [];
        $previousRaw = null;
        $lastStillTracking = null;

        foreach ($this->orderedTrackings($rawTrackings) as $tracking) {
            $type = $this->timelineType($tracking, $previousRaw);
            $sessionBreak = $previousRaw ? $this->shouldBreakSegment($previousRaw, $tracking, $settings) : false;

            if ($sessionBreak) {
                $lastStillTracking = null;
            }

            if ($this->isMovementType($type)
                && ! $currentSegment
                && $lastStillTracking
                && $this->isPostStillDrift($lastStillTracking, $tracking, $settings)) {
                $type = 'still';
            }

            $diagnostic = $this->baseDiagnostic($tracking, $type);

            if ($sessionBreak || ! $this->isMovementType($type)) {
                $this->flushSegment($segments, $currentSegment, $settings);
                $currentSegment = null;
                $acceptedMovement = [];
            }

            if (! $this->isInsideAttendanceSession($tracking)) {
                $this->rejectDiagnostic($diagnostic, 'missing_attendance', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            if (! $this->hasValidCoordinates($tracking)) {
                $this->rejectDiagnostic($diagnostic, 'invalid_coordinates', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            if ((bool) ($tracking->is_mock_location ?? false)) {
                $this->rejectDiagnostic($diagnostic, 'mock_location', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            $accuracy = $tracking->accuracy !== null ? (float) $tracking->accuracy : null;
            if ($accuracy === null || $accuracy <= 0 || $accuracy > (float) $settings['gps_max_accuracy_metres']) {
                $this->rejectDiagnostic($diagnostic, 'accuracy_exceeded', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            $timestampKey = $this->timestampKey($tracking);
            if ($timestampKey !== null && isset($seenTimestamps[$timestampKey])) {
                $this->rejectDiagnostic($diagnostic, 'duplicate_timestamp', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            $coordinateKey = $this->coordinateKey($tracking);
            if (isset($seenCoordinates[$coordinateKey])) {
                $this->rejectDiagnostic($diagnostic, 'duplicate_location', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            $item = $this->itemFromTracking($tracking, $type, $sessionBreak);

            if (! $this->isMovementType($type)) {
                $items->push($item);
                $this->acceptDiagnostic($diagnostic, null);
                $diagnostics[] = $diagnostic;
                if ($type === 'still') {
                    $lastStillTracking = $tracking;
                }
                $seenCoordinates[$coordinateKey] = true;
                if ($timestampKey !== null) {
                    $seenTimestamps[$timestampKey] = true;
                }
                $previousRaw = $tracking;
                continue;
            }

            $previous = $acceptedMovement[count($acceptedMovement) - 1] ?? null;
            $previousPrevious = $acceptedMovement[count($acceptedMovement) - 2] ?? null;

            if (! $previous) {
                $currentSegment = $this->newSegment($tracking, ++$segmentNumber);
                $this->addMovementPoint($currentSegment, $tracking, $diagnostic);
                $acceptedMovement[] = $tracking;
                $lastStillTracking = null;
                $items->push($item);
                $this->acceptDiagnostic($diagnostic, $segmentNumber);
                $diagnostics[] = $diagnostic;
                $seenCoordinates[$coordinateKey] = true;
                if ($timestampKey !== null) {
                    $seenTimestamps[$timestampKey] = true;
                }
                $previousRaw = $tracking;
                continue;
            }

            $metrics = $this->movementMetrics($previous, $tracking, $previousPrevious, $settings);
            $diagnostic = [...$diagnostic, ...$metrics];

            if (($metrics['time_difference_seconds'] ?? 0) <= 0) {
                $this->rejectDiagnostic($diagnostic, 'invalid_timestamp', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            if (($metrics['distance_metres'] ?? 0) <= (float) $settings['gps_min_distance_metres']) {
                $this->rejectDiagnostic($diagnostic, 'distance_below_threshold', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            if (($metrics['speed_mps'] ?? 0) > (float) $settings['gps_max_speed_mps']) {
                $this->rejectDiagnostic($diagnostic, 'speed_exceeded', $reasons);
                $diagnostics[] = $diagnostic;
                $previousRaw = $tracking;
                continue;
            }

            $spike = $previousPrevious
                ? $this->spikeDecision($previousPrevious, $previous, $tracking, $settings)
                : ['reject_previous' => false, 'metrics' => []];

            $diagnostic = [...$diagnostic, ...$spike['metrics']];

            if ($spike['reject_previous']) {
                $removed = array_pop($acceptedMovement);
                if ($currentSegment) {
                    array_pop($currentSegment['unsimplified_points']);
                }
                $removedItem = $items->pop();
                if ($removedItem && ($removedItem['id'] ?? null) === $removed?->id) {
                    $removedItem['type'] = 'still';
                    $removedItem['distance'] = 0;
                    $removedItem['spikeRejected'] = true;
                    $items->push($removedItem);
                }

                foreach ($diagnostics as &$previousDiagnostic) {
                    if (($previousDiagnostic['id'] ?? null) === $removed?->id) {
                        $previousDiagnostic['accepted'] = false;
                        $previousDiagnostic['reason'] = 'angle_spike';
                        $previousDiagnostic['segment_number'] = null;
                        $previousDiagnostic['spike_removed'] = true;
                        $previousDiagnostic = [...$previousDiagnostic, ...$spike['metrics']];
                        $reasons['angle_spike'] = ($reasons['angle_spike'] ?? 0) + 1;
                        break;
                    }
                }
                unset($previousDiagnostic);

                $lastAccepted = $acceptedMovement[count($acceptedMovement) - 1] ?? null;
                if ($lastAccepted) {
                    $metrics = $this->movementMetrics($lastAccepted, $tracking, $acceptedMovement[count($acceptedMovement) - 2] ?? null, $settings);
                    $diagnostic = [...$diagnostic, ...$metrics, 'spike_removed_id' => $removed?->id];
                }
            }

            $this->addMovementPoint($currentSegment, $tracking, $diagnostic);
            $acceptedMovement[] = $tracking;
            $lastStillTracking = null;
            $items->push($item);
            $this->acceptDiagnostic($diagnostic, $segmentNumber);
            $diagnostics[] = $diagnostic;
            $seenCoordinates[$coordinateKey] = true;
            if ($timestampKey !== null) {
                $seenTimestamps[$timestampKey] = true;
            }
            $previousRaw = $tracking;
        }

        $this->flushSegment($segments, $currentSegment, $settings);

        return [
            'items' => $items->values(),
            'polylineSegments' => $segments,
            'polylinePoints' => collect($segments)->pluck('points')->flatten(1)->values(),
            'directionsSegments' => $this->directionsSegments($segments),
            'totalKM' => round((float) collect($segments)->sum('distance_km'), 2),
            'diagnostics' => $diagnostics,
            'rejectionReasons' => $reasons,
            'settings' => $settings,
        ];
    }

    public function timelineType(LocationTracking $tracking, ?LocationTracking $previous = null, ?LocationTracking $next = null): string
    {
        $trackingType = strtolower((string) $tracking->type);

        return match (true) {
            $trackingType === 'checked_in' => 'checkIn',
            $trackingType === 'checked_out' => 'checkOut',
            $trackingType === 'still' => 'still',
            $trackingType === 'proof_post' => 'proofPost',
            in_array(strtolower((string) $tracking->activity), ['activitytype.still', 'still'], true) => 'still',
            in_array(strtolower((string) $tracking->activity), ['activitytype.walking', 'walking', 'walk'], true) => 'walk',
            in_array(strtolower((string) $tracking->activity), ['activitytype.in_vehicle', 'in_vehicle', 'vehicle', 'travelling'], true)
                || in_array($trackingType, ['travelling', 'vehicle'], true) => 'vehicle',
            default => 'still',
        };
    }

    public function shouldBreakSegment(LocationTracking $previous, LocationTracking $current, ?array $settings = null): bool
    {
        $settings ??= $this->gpsValidator->settings();

        if ($previous->employee_id !== $current->employee_id
            || $previous->attendance_id !== $current->attendance_id
            || (string) $previous->device_id !== (string) $current->device_id) {
            return true;
        }

        if (! $previous->recorded_at || ! $current->recorded_at) {
            return false;
        }

        if ($previous->recorded_at->toDateString() !== $current->recorded_at->toDateString()) {
            return true;
        }

        $seconds = $current->recorded_at->getTimestamp() - $previous->recorded_at->getTimestamp();
        if ($seconds <= 0 || $seconds > (int) ($settings['gps_max_inactive_gap_seconds'] ?? 600)) {
            return true;
        }

        $distanceKm = $this->distanceMetres($previous, $current) / 1000;
        $speedKmh = ($distanceKm / max(1, $seconds)) * 3600;

        return $distanceKm > 2 || $speedKmh > ((float) ($settings['gps_max_speed_mps'] ?? 25) * 3.6);
    }

    private function orderedTrackings(Collection $trackings): Collection
    {
        return $trackings
            ->sortBy([
                fn (LocationTracking $a, LocationTracking $b) => (($a->recorded_at ?? $a->created_at)?->getTimestamp() ?? 0) <=> (($b->recorded_at ?? $b->created_at)?->getTimestamp() ?? 0),
                fn (LocationTracking $a, LocationTracking $b) => ($a->id ?? 0) <=> ($b->id ?? 0),
            ])
            ->values();
    }

    private function itemFromTracking(LocationTracking $tracking, string $type, bool $segmentBreak): array
    {
        return [
            'id' => $tracking->id,
            'employeeId' => $tracking->employee_id,
            'attendanceId' => $tracking->attendance_id,
            'deviceId' => $tracking->device_id,
            'type' => $type,
            'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
            'bearing' => $tracking->bearing !== null ? (float) $tracking->bearing : null,
            'activity' => $tracking->activity,
            'batteryPercentage' => $tracking->battery_percentage,
            'isGPSOn' => (bool) $tracking->is_gps_on,
            'isWifiOn' => false,
            'latitude' => (float) $tracking->latitude,
            'longitude' => (float) $tracking->longitude,
            'address' => null,
            'signalStrength' => null,
            'trackingType' => $tracking->type,
            'segmentBreakBefore' => $segmentBreak,
            'startTime' => $tracking->recorded_at?->format('h:i A'),
            'endTime' => $tracking->recorded_at?->format('h:i A'),
            'elapseTime' => '00:00:00',
            'distance' => 0,
        ];
    }

    private function newSegment(LocationTracking $tracking, int $number): array
    {
        return [
            'segment_number' => $number,
            'employee_id' => $tracking->employee_id,
            'attendance_id' => $tracking->attendance_id,
            'device_id' => $tracking->device_id,
            'start_type' => $tracking->type,
            'end_type' => null,
            'distance_km' => 0,
            'points' => [],
            'unsimplified_points' => [],
        ];
    }

    private function addMovementPoint(?array &$segment, LocationTracking $tracking, array $metrics): void
    {
        if (! $segment) {
            return;
        }

        $segment['unsimplified_points'][] = [
            'lat' => (float) $tracking->latitude,
            'lng' => (float) $tracking->longitude,
            'id' => $tracking->id,
            'recorded_at' => $tracking->recorded_at?->format('h:i A'),
            'activity' => $tracking->activity,
            'type' => $tracking->type,
            'distance_metres' => $metrics['distance_metres'] ?? null,
            'speed_mps' => $metrics['speed_mps'] ?? null,
            'bearing' => $metrics['bearing'] ?? null,
            'bearing_difference' => $metrics['bearing_difference'] ?? null,
        ];
    }

    private function flushSegment(array &$segments, ?array &$segment, array $settings): void
    {
        if (! $segment) {
            return;
        }

        if (count($segment['unsimplified_points']) >= 2) {
            $segment['distance_km'] = round($this->pointsDistanceKm($segment['unsimplified_points']), 2);
            $segment['points'] = $this->simplifyPoints(
                $segment['unsimplified_points'],
                (float) ($settings['gps_douglas_peucker_tolerance_metres'] ?? 3)
            );
            $segments[] = $segment;
        }

        $segment = null;
    }

    private function directionsSegments(array $segments): array
    {
        return collect($segments)
            ->map(function (array $segment): ?array {
                $points = $segment['points'] ?? [];
                if (count($points) < 2) {
                    return null;
                }

                $origin = $points[0];
                $destination = $points[count($points) - 1];
                $waypoints = $this->directionWaypoints($points);

                return [
                    'segment_number' => $segment['segment_number'] ?? null,
                    'employee_id' => $segment['employee_id'] ?? null,
                    'attendance_id' => $segment['attendance_id'] ?? null,
                    'device_id' => $segment['device_id'] ?? null,
                    'travel_mode' => $this->directionsTravelMode($points),
                    'origin' => $this->directionsPoint($origin),
                    'destination' => $this->directionsPoint($destination),
                    'waypoints' => array_map(fn (array $point): array => $this->directionsPoint($point), $waypoints),
                    'source_point_ids' => collect($points)->pluck('id')->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function directionWaypoints(array $points): array
    {
        $middle = array_slice($points, 1, -1);
        $maxWaypoints = 23;

        if (count($middle) <= $maxWaypoints) {
            return array_values($middle);
        }

        $selected = [];
        $step = count($middle) / $maxWaypoints;
        for ($index = 0; $index < $maxWaypoints; $index++) {
            $selectedIndex = (int) floor($index * $step);
            $selected[] = $middle[min($selectedIndex, count($middle) - 1)];
        }

        return collect($selected)
            ->unique(fn (array $point): string => (string) ($point['id'] ?? ($point['lat'] . ',' . $point['lng'])))
            ->values()
            ->all();
    }

    private function directionsTravelMode(array $points): string
    {
        $hasVehicle = collect($points)->contains(function (array $point): bool {
            return in_array(strtolower((string) ($point['activity'] ?? '')), ['activitytype.in_vehicle', 'in_vehicle', 'vehicle', 'travelling'], true)
                || in_array(strtolower((string) ($point['type'] ?? '')), ['vehicle'], true);
        });

        return $hasVehicle ? 'DRIVING' : 'WALKING';
    }

    private function directionsPoint(array $point): array
    {
        return [
            'lat' => (float) $point['lat'],
            'lng' => (float) $point['lng'],
            'id' => $point['id'] ?? null,
            'recorded_at' => $point['recorded_at'] ?? null,
        ];
    }

    private function movementMetrics(LocationTracking $previous, LocationTracking $current, ?LocationTracking $previousPrevious, array $settings): array
    {
        $distance = $this->distanceMetres($previous, $current);
        $time = $current->recorded_at && $previous->recorded_at
            ? $current->recorded_at->getTimestamp() - $previous->recorded_at->getTimestamp()
            : 0;
        $speed = $time > 0 ? $distance / $time : null;
        $bearing = $this->gpsValidator->bearingDegrees(
            (float) $previous->latitude,
            (float) $previous->longitude,
            (float) $current->latitude,
            (float) $current->longitude
        );
        $bearingDifference = null;

        if ($previousPrevious) {
            $previousDistance = $this->distanceMetres($previousPrevious, $previous);
            if ($previousDistance >= (float) $settings['gps_bearing_min_distance_metres']
                && $distance >= (float) $settings['gps_bearing_min_distance_metres']
                && (float) $previous->accuracy <= (float) $settings['gps_max_accuracy_metres']
                && (float) $current->accuracy <= (float) $settings['gps_max_accuracy_metres']) {
                $previousBearing = $this->gpsValidator->bearingDegrees(
                    (float) $previousPrevious->latitude,
                    (float) $previousPrevious->longitude,
                    (float) $previous->latitude,
                    (float) $previous->longitude
                );
                $bearingDifference = $this->gpsValidator->bearingDifferenceDegrees($previousBearing, $bearing);
            }
        }

        return [
            'distance_metres' => $distance,
            'time_difference_seconds' => $time,
            'speed_mps' => $speed,
            'speed_kmph' => $speed !== null ? $speed * 3.6 : null,
            'bearing' => $bearing,
            'bearing_difference' => $bearingDifference,
            'bearing_suspicious' => $bearingDifference !== null
                && $bearingDifference > (float) $settings['gps_max_bearing_change_degrees'],
        ];
    }

    private function spikeDecision(LocationTracking $a, LocationTracking $b, LocationTracking $c, array $settings): array
    {
        $ab = $this->distanceMetres($a, $b);
        $bc = $this->distanceMetres($b, $c);
        $ac = $this->distanceMetres($a, $c);
        $angle = $this->turnAngleDegrees($a, $b, $c);
        $bAccuracy = (float) ($b->accuracy ?? 999);
        $edgeAccuracy = max((float) ($a->accuracy ?? 999), (float) ($c->accuracy ?? 999));
        $detourRatio = ($ab + $bc) / max(1.0, $ac);
        $timeAb = $b->recorded_at && $a->recorded_at ? max(1, $b->recorded_at->getTimestamp() - $a->recorded_at->getTimestamp()) : 1;
        $timeBc = $c->recorded_at && $b->recorded_at ? max(1, $c->recorded_at->getTimestamp() - $b->recorded_at->getTimestamp()) : 1;
        $detourSpeed = ($ab + $bc) / ($timeAb + $timeBc);

        $reject = $ab >= (float) $settings['gps_bearing_min_distance_metres']
            && $bc >= (float) $settings['gps_bearing_min_distance_metres']
            && $detourRatio >= 2.2
            && $angle <= 35
            && $bAccuracy > $edgeAccuracy
            && $detourSpeed <= (float) $settings['gps_max_speed_mps'];

        return [
            'reject_previous' => $reject,
            'metrics' => [
                'angle' => $angle,
                'spike_detour_ratio' => $detourRatio,
                'spike_removed' => $reject,
            ],
        ];
    }

    private function turnAngleDegrees(LocationTracking $a, LocationTracking $b, LocationTracking $c): float
    {
        $latScale = 111320;
        $lngScale = 111320 * cos(deg2rad((float) $b->latitude));
        $baX = ((float) $a->longitude - (float) $b->longitude) * $lngScale;
        $baY = ((float) $a->latitude - (float) $b->latitude) * $latScale;
        $bcX = ((float) $c->longitude - (float) $b->longitude) * $lngScale;
        $bcY = ((float) $c->latitude - (float) $b->latitude) * $latScale;
        $baLen = sqrt(($baX * $baX) + ($baY * $baY));
        $bcLen = sqrt(($bcX * $bcX) + ($bcY * $bcY));

        if ($baLen <= 0 || $bcLen <= 0) {
            return 180.0;
        }

        $cos = max(-1.0, min(1.0, (($baX * $bcX) + ($baY * $bcY)) / ($baLen * $bcLen)));

        return rad2deg(acos($cos));
    }

    private function simplifyPoints(array $points, float $toleranceMeters): array
    {
        if (count($points) <= 2 || $toleranceMeters <= 0) {
            return array_values($points);
        }

        $keep = [0 => true, count($points) - 1 => true];
        $stack = [[0, count($points) - 1]];

        while ($range = array_pop($stack)) {
            [$start, $end] = $range;
            if ($end <= $start + 1) {
                continue;
            }

            $maxDistance = 0.0;
            $maxIndex = $start;
            for ($index = $start + 1; $index < $end; $index++) {
                $distance = $this->perpendicularDistanceMeters($points[$index], $points[$start], $points[$end]);
                if ($distance > $maxDistance) {
                    $maxDistance = $distance;
                    $maxIndex = $index;
                }
            }

            if ($maxDistance > $toleranceMeters) {
                $keep[$maxIndex] = true;
                $stack[] = [$start, $maxIndex];
                $stack[] = [$maxIndex, $end];
            }
        }

        ksort($keep);

        return array_values(array_intersect_key($points, $keep));
    }

    private function perpendicularDistanceMeters(array $point, array $start, array $end): float
    {
        $latScale = 111320;
        $lngScale = 111320 * cos(deg2rad($start['lat']));
        $x = ($point['lng'] - $start['lng']) * $lngScale;
        $y = ($point['lat'] - $start['lat']) * $latScale;
        $x2 = ($end['lng'] - $start['lng']) * $lngScale;
        $y2 = ($end['lat'] - $start['lat']) * $latScale;
        $lengthSquared = ($x2 * $x2) + ($y2 * $y2);

        if ($lengthSquared <= 0) {
            return sqrt(($x * $x) + ($y * $y));
        }

        $projection = max(0, min(1, (($x * $x2) + ($y * $y2)) / $lengthSquared));

        return sqrt((($x - ($projection * $x2)) ** 2) + (($y - ($projection * $y2)) ** 2));
    }

    private function pointsDistanceKm(array $points): float
    {
        $distance = 0.0;
        for ($index = 1; $index < count($points); $index++) {
            $distance += $this->gpsValidator->distanceMetres(
                $points[$index - 1]['lat'],
                $points[$index - 1]['lng'],
                $points[$index]['lat'],
                $points[$index]['lng'],
            );
        }

        return $distance / 1000;
    }

    private function distanceMetres(LocationTracking $from, LocationTracking $to): float
    {
        return $this->gpsValidator->distanceMetres(
            (float) $from->latitude,
            (float) $from->longitude,
            (float) $to->latitude,
            (float) $to->longitude
        );
    }

    private function hasValidCoordinates(LocationTracking $tracking): bool
    {
        $latitude = $tracking->latitude !== null ? (float) $tracking->latitude : null;
        $longitude = $tracking->longitude !== null ? (float) $tracking->longitude : null;

        return $latitude !== null
            && $longitude !== null
            && ! ($latitude === 0.0 && $longitude === 0.0)
            && $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }

    private function isInsideAttendanceSession(LocationTracking $tracking): bool
    {
        return (bool) $tracking->attendance;
    }

    private function isMovementType(?string $type): bool
    {
        return in_array($type, ['vehicle', 'walk'], true);
    }

    private function isPostStillDrift(LocationTracking $still, LocationTracking $current, array $settings): bool
    {
        if (! $still->recorded_at || ! $current->recorded_at) {
            return false;
        }

        $distance = $this->distanceMetres($still, $current);
        if ($distance > 30) {
            return false;
        }

        $seconds = max(1, $current->recorded_at->getTimestamp() - $still->recorded_at->getTimestamp());
        $computedSpeed = $distance / $seconds;
        $reportedSpeed = $current->speed !== null ? (float) $current->speed : null;

        return $computedSpeed <= 0.8
            && ($reportedSpeed === null || $reportedSpeed <= 0.8)
            && (float) ($current->accuracy ?? 999) <= (float) $settings['gps_max_accuracy_metres'];
    }

    private function timestampKey(LocationTracking $tracking): ?string
    {
        return $tracking->recorded_at ? (string) $tracking->recorded_at->getTimestamp() : null;
    }

    private function coordinateKey(LocationTracking $tracking): string
    {
        return round((float) $tracking->latitude, 7) . ',' . round((float) $tracking->longitude, 7);
    }

    private function baseDiagnostic(LocationTracking $tracking, string $type): array
    {
        return [
            'id' => $tracking->id,
            'employee_id' => $tracking->employee_id,
            'attendance_id' => $tracking->attendance_id,
            'device_id' => $tracking->device_id,
            'type' => $tracking->type,
            'timeline_type' => $type,
            'activity' => $tracking->activity,
            'latitude' => $tracking->latitude !== null ? (float) $tracking->latitude : null,
            'longitude' => $tracking->longitude !== null ? (float) $tracking->longitude : null,
            'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
            'recorded_at' => $tracking->recorded_at?->toDateTimeString(),
            'distance_metres' => null,
            'time_difference_seconds' => null,
            'speed_mps' => null,
            'bearing' => null,
            'bearing_difference' => null,
            'angle' => null,
            'accepted' => false,
            'reason' => null,
            'segment_number' => null,
        ];
    }

    private function rejectDiagnostic(array &$diagnostic, string $reason, array &$reasons): void
    {
        $diagnostic['accepted'] = false;
        $diagnostic['reason'] = $reason;
        $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
    }

    private function acceptDiagnostic(array &$diagnostic, ?int $segmentNumber): void
    {
        $diagnostic['accepted'] = true;
        $diagnostic['reason'] = null;
        $diagnostic['segment_number'] = $segmentNumber;
    }

    private function formatSecondsAsClock(int|float $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
