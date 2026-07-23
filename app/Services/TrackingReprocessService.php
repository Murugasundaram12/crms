<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TrackingReprocessService
{
    public function __construct(
        private readonly EmployeeTimelineBuilder $timelineBuilder,
        private readonly GpsTrackingValidationService $gpsValidator,
    ) {
    }

    public function reprocess(?int $employeeId = null, ?string $date = null, bool $all = false, bool $dryRun = false): array
    {
        if (! $all && ! $employeeId && ! $date) {
            throw new InvalidArgumentException('Use --all, --date, or --employee to choose old tracking data for reprocess.');
        }

        $query = Attendance::query()
            ->with('user')
            ->orderBy('attendance_date')
            ->orderBy('check_in_at')
            ->orderBy('id');

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        if ($date) {
            $query->whereDate('attendance_date', Carbon::parse($date)->toDateString());
        }

        $sessions = [];
        $totals = [
            'attendance_count' => 0,
            'tracking_rows' => 0,
            'accepted_rows' => 0,
            'ignored_rows' => 0,
            'route_segments' => 0,
            'route_points' => 0,
            'gps_distance_km' => 0.0,
            'rejection_reasons' => [],
            'dry_run' => $dryRun,
            'flags_written' => false,
        ];

        $query->chunkById(100, function ($attendances) use (&$sessions, &$totals, $dryRun): void {
            foreach ($attendances as $attendance) {
                $summary = $this->reprocessAttendance($attendance, $dryRun);
                $sessions[] = $summary;

                $totals['attendance_count']++;
                $totals['tracking_rows'] += $summary['tracking_rows'];
                $totals['accepted_rows'] += $summary['accepted_rows'];
                $totals['ignored_rows'] += $summary['ignored_rows'];
                $totals['route_segments'] += $summary['route_segments'];
                $totals['route_points'] += $summary['route_points'];
                $totals['gps_distance_km'] += $summary['gps_distance_km'];
                $totals['flags_written'] = $totals['flags_written'] || $summary['flags_written'];

                foreach ($summary['rejection_reasons'] as $reason => $count) {
                    $totals['rejection_reasons'][$reason] = ($totals['rejection_reasons'][$reason] ?? 0) + $count;
                }
            }
        });

        $totals['gps_distance_km'] = round($totals['gps_distance_km'], 2);

        return [
            'summary' => $totals,
            'sessions' => $sessions,
        ];
    }

    public function reprocessAttendance(Attendance $attendance, bool $dryRun = false): array
    {
        $trackings = LocationTracking::query()
            ->with('attendance')
            ->where('attendance_id', $attendance->id)
            ->orderByRaw('COALESCE(recorded_at, created_at) ASC')
            ->orderBy('id')
            ->get();

        $timeline = $this->timelineBuilder->build($trackings, $this->timelineOptions());
        $diagnostics = collect($timeline['diagnostics'] ?? [])->keyBy('id');
        $drawableSegmentByPointId = $this->drawableSegmentByPointId($timeline['polylineSegments'] ?? []);
        $flagsWritable = $this->hasProcessingColumns();

        if (! $dryRun && $flagsWritable) {
            DB::transaction(function () use ($trackings, $diagnostics, $drawableSegmentByPointId): void {
                $processedAt = now();

                foreach ($trackings as $tracking) {
                    $diagnostic = $diagnostics->get($tracking->id);
                    $accepted = (bool) ($diagnostic['accepted'] ?? false);

                    $tracking->forceFill([
                        'is_ignored' => ! $accepted,
                        'ignored_reason' => $accepted ? null : ($diagnostic['reason'] ?? 'not_processed'),
                        'processed_at' => $processedAt,
                        'segment_index' => $accepted ? ($drawableSegmentByPointId[$tracking->id] ?? null) : null,
                    ])->save();
                }
            });
        }

        $acceptedRows = $diagnostics->filter(fn (array $row): bool => (bool) ($row['accepted'] ?? false))->count();
        $ignoredRows = $diagnostics->filter(fn (array $row): bool => ! (bool) ($row['accepted'] ?? false))->count();
        $routeSegments = collect($timeline['polylineSegments'] ?? []);

        return [
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->user_id,
            'employee_name' => trim((string) ($attendance->user?->name ?? $attendance->user?->full_name ?? '')),
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
            'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
            'tracking_rows' => $trackings->count(),
            'accepted_rows' => $acceptedRows,
            'ignored_rows' => $ignoredRows,
            'route_segments' => $routeSegments->count(),
            'route_points' => (int) $routeSegments->sum(fn (array $segment): int => count($segment['points'] ?? [])),
            'gps_distance_km' => round((float) ($timeline['gpsDistanceKm'] ?? 0), 2),
            'first_tracking_at' => $trackings->first()?->recorded_at?->toDateTimeString()
                ?? $trackings->first()?->created_at?->toDateTimeString(),
            'last_tracking_at' => $trackings->last()?->recorded_at?->toDateTimeString()
                ?? $trackings->last()?->created_at?->toDateTimeString(),
            'rejection_reasons' => $timeline['rejectionReasons'] ?? [],
            'flags_written' => ! $dryRun && $flagsWritable,
            'flags_available' => $flagsWritable,
        ];
    }

    public function diagnoseOld(int $employeeId, string $date): array
    {
        $date = Carbon::parse($date)->toDateString();
        $start = Carbon::parse($date, config('app.timezone', 'UTC'))->startOfDay();
        $end = $start->copy()->endOfDay();
        $employee = User::query()->find($employeeId);
        $attendances = $this->dateSafeAttendances($employeeId, $date, $start, $end);
        $sessions = [];
        $totals = [
            'employee_id' => $employeeId,
            'employee_name' => $employee?->name,
            'date' => $date,
            'attendance_count' => $attendances->count(),
            'raw_record_count' => 0,
            'accepted_count' => 0,
            'rejected_count' => 0,
            'segment_count' => 0,
            'raw_distance_km' => 0.0,
            'cleaned_distance_km' => 0.0,
            'rejected_by_reason' => [],
            'problematic_record_ids' => [],
        ];

        foreach ($attendances as $attendance) {
            $trackings = $this->dateSafeTrackings(collect([$attendance]), $start, $end);
            $timeline = $this->timelineBuilder->build($trackings, $this->timelineOptions());
            $diagnostics = collect($timeline['diagnostics'] ?? []);
            $rejected = $diagnostics->filter(fn (array $row): bool => ! (bool) ($row['accepted'] ?? false));
            $rawDistanceKm = $this->rawDistanceKm($trackings);
            $largestGaps = $this->largestTimeGaps($trackings);
            $largestRawSteps = $this->largestRawSteps($trackings);
            $problematicIds = $rejected
                ->pluck('id')
                ->merge(collect($largestRawSteps)->pluck('from_id'))
                ->merge(collect($largestRawSteps)->pluck('to_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $session = [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->user_id,
                'employee_name' => trim((string) ($attendance->user?->name ?? $attendance->user?->full_name ?? '')),
                'attendance_date' => $attendance->attendance_date?->toDateString(),
                'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
                'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
                'raw_record_count' => $trackings->count(),
                'accepted_count' => $diagnostics->count() - $rejected->count(),
                'rejected_count' => $rejected->count(),
                'rejected_by_reason' => $timeline['rejectionReasons'] ?? [],
                'segment_count' => count($timeline['polylineSegments'] ?? []),
                'route_point_count' => (int) collect($timeline['polylineSegments'] ?? [])->sum(fn (array $segment): int => count($segment['points'] ?? [])),
                'raw_distance_km' => round($rawDistanceKm, 2),
                'cleaned_distance_km' => round((float) ($timeline['gpsDistanceKm'] ?? 0), 2),
                'largest_time_gaps' => $largestGaps,
                'largest_raw_steps' => $largestRawSteps,
                'rejected_record_ids' => $rejected->pluck('id')->filter()->values()->all(),
                'problematic_record_ids' => $problematicIds,
            ];

            $sessions[] = $session;
            $totals['raw_record_count'] += $session['raw_record_count'];
            $totals['accepted_count'] += $session['accepted_count'];
            $totals['rejected_count'] += $session['rejected_count'];
            $totals['segment_count'] += $session['segment_count'];
            $totals['raw_distance_km'] += $session['raw_distance_km'];
            $totals['cleaned_distance_km'] += $session['cleaned_distance_km'];
            $totals['problematic_record_ids'] = array_values(array_unique([
                ...$totals['problematic_record_ids'],
                ...$problematicIds,
            ]));

            foreach ($session['rejected_by_reason'] as $reason => $count) {
                $totals['rejected_by_reason'][$reason] = ($totals['rejected_by_reason'][$reason] ?? 0) + $count;
            }
        }

        $totals['raw_distance_km'] = round($totals['raw_distance_km'], 2);
        $totals['cleaned_distance_km'] = round($totals['cleaned_distance_km'], 2);

        return [
            'summary' => $totals,
            'sessions' => $sessions,
        ];
    }

    private function timelineOptions(): array
    {
        $settings = $this->gpsValidator->settings();

        return [
            'minimum_distance_meters' => (float) ($settings['gps_min_distance_metres'] ?? 30),
            'max_accuracy_meters' => (float) ($settings['gps_max_accuracy_metres'] ?? 50),
            'max_computed_speed_kmh' => (float) (($settings['gps_max_speed_mps'] ?? 25) * 3.6),
            'max_bearing_change_degrees' => (float) ($settings['gps_max_bearing_change_degrees'] ?? 45),
            'bearing_drift_distance_meters' => (float) ($settings['gps_bearing_min_distance_metres'] ?? 10),
            'tracking_interval_seconds' => (int) ($settings['tracking_interval_seconds'] ?? 30),
            'gps_max_inactive_gap_seconds' => (int) ($settings['gps_max_inactive_gap_seconds'] ?? 600),
            'large_gap_distance_meters' => (float) ($settings['large_gap_distance_meters'] ?? 2000),
            'douglas_peucker_tolerance_meters' => (float) ($settings['gps_douglas_peucker_tolerance_metres'] ?? 15),
            'simplify_after_points' => (int) ($settings['timeline_simplify_after_points'] ?? 1000),
        ];
    }

    private function dateSafeAttendances(int $employeeId, string $date, Carbon $start, Carbon $end): Collection
    {
        return Attendance::query()
            ->with('user')
            ->where('user_id', $employeeId)
            ->where(function ($query) use ($date, $start, $end): void {
                $query->whereDate('attendance_date', $date)
                    ->orWhereBetween('check_in_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                    ->orWhereBetween('check_out_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                    ->orWhereExists(function ($subQuery) use ($start, $end): void {
                        $subQuery->selectRaw('1')
                            ->from('location_trackings')
                            ->whereColumn('location_trackings.attendance_id', 'attendances.id')
                            ->whereRaw('COALESCE(location_trackings.recorded_at, location_trackings.created_at) BETWEEN ? AND ?', [
                                $start->toDateTimeString(),
                                $end->toDateTimeString(),
                            ]);
                    });
            })
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();
    }

    private function dateSafeTrackings(Collection $attendances, Carbon $start, Carbon $end): Collection
    {
        if ($attendances->isEmpty()) {
            return collect();
        }

        return LocationTracking::query()
            ->with('attendance')
            ->whereIn('attendance_id', $attendances->pluck('id'))
            ->whereRaw('COALESCE(recorded_at, created_at) BETWEEN ? AND ?', [
                $start->toDateTimeString(),
                $end->toDateTimeString(),
            ])
            ->orderByRaw('COALESCE(recorded_at, created_at) ASC')
            ->orderBy('id')
            ->get();
    }

    private function rawDistanceKm(Collection $trackings): float
    {
        $distance = 0.0;
        $previous = null;

        foreach ($trackings as $tracking) {
            if (! $this->hasValidCoordinates($tracking)) {
                continue;
            }

            if ($previous
                && $previous->attendance_id === $tracking->attendance_id
                && (string) $previous->device_id === (string) $tracking->device_id
                && $this->trackingTime($previous)?->toDateString() === $this->trackingTime($tracking)?->toDateString()) {
                $distance += $this->gpsValidator->distanceMetres(
                    (float) $previous->latitude,
                    (float) $previous->longitude,
                    (float) $tracking->latitude,
                    (float) $tracking->longitude,
                );
            }

            $previous = $tracking;
        }

        return $distance / 1000;
    }

    private function largestTimeGaps(Collection $trackings, int $limit = 10): array
    {
        $gaps = [];
        $previous = null;

        foreach ($trackings as $tracking) {
            $currentTime = $this->trackingTime($tracking);
            $previousTime = $previous ? $this->trackingTime($previous) : null;

            if ($previous && $previousTime && $currentTime) {
                $gaps[] = [
                    'from_id' => $previous->id,
                    'to_id' => $tracking->id,
                    'from_recorded_at' => $previousTime->toDateTimeString(),
                    'to_recorded_at' => $currentTime->toDateTimeString(),
                    'gap_seconds' => $previousTime->diffInSeconds($currentTime),
                ];
            }

            $previous = $tracking;
        }

        usort($gaps, fn (array $a, array $b): int => $b['gap_seconds'] <=> $a['gap_seconds']);

        return array_slice($gaps, 0, $limit);
    }

    private function largestRawSteps(Collection $trackings, int $limit = 10): array
    {
        $steps = [];
        $previous = null;

        foreach ($trackings as $tracking) {
            if (! $this->hasValidCoordinates($tracking)) {
                continue;
            }

            if ($previous
                && $previous->attendance_id === $tracking->attendance_id
                && (string) $previous->device_id === (string) $tracking->device_id
                && $this->trackingTime($previous)?->toDateString() === $this->trackingTime($tracking)?->toDateString()) {
                $previousTime = $this->trackingTime($previous);
                $currentTime = $this->trackingTime($tracking);
                $distanceMetres = $this->gpsValidator->distanceMetres(
                    (float) $previous->latitude,
                    (float) $previous->longitude,
                    (float) $tracking->latitude,
                    (float) $tracking->longitude,
                );
                $seconds = $previousTime && $currentTime ? max(0, $previousTime->diffInSeconds($currentTime)) : null;

                $steps[] = [
                    'from_id' => $previous->id,
                    'to_id' => $tracking->id,
                    'from_recorded_at' => $previousTime?->toDateTimeString(),
                    'to_recorded_at' => $currentTime?->toDateTimeString(),
                    'distance_metres' => round($distanceMetres, 2),
                    'gap_seconds' => $seconds,
                    'speed_kmph' => $seconds && $seconds > 0 ? round(($distanceMetres / $seconds) * 3.6, 2) : null,
                ];
            }

            $previous = $tracking;
        }

        usort($steps, fn (array $a, array $b): int => $b['distance_metres'] <=> $a['distance_metres']);

        return array_slice($steps, 0, $limit);
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

    private function trackingTime(LocationTracking $tracking): ?Carbon
    {
        return $tracking->recorded_at ?? $tracking->created_at;
    }

    private function drawableSegmentByPointId(array $segments): array
    {
        $map = [];

        foreach ($segments as $segment) {
            $segmentNumber = $segment['segment_number'] ?? null;
            if (! $segmentNumber) {
                continue;
            }

            $points = $segment['unsimplified_points'] ?? $segment['points'] ?? [];
            if (count($points) < 2) {
                continue;
            }

            foreach ($points as $point) {
                if (isset($point['id'])) {
                    $map[(int) $point['id']] = (int) $segmentNumber;
                }
            }
        }

        return $map;
    }

    private function hasProcessingColumns(): bool
    {
        return Schema::hasTable('location_trackings')
            && Schema::hasColumn('location_trackings', 'is_ignored')
            && Schema::hasColumn('location_trackings', 'ignored_reason')
            && Schema::hasColumn('location_trackings', 'processed_at')
            && Schema::hasColumn('location_trackings', 'segment_index');
    }
}
