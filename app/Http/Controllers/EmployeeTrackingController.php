<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\EmployeeDevice;
use App\Models\LocationTracking;
use App\Models\User;
use App\Services\EmployeeTimelineBuilder;
use App\Services\GpsTrackingValidationService;
use App\Services\TimelineGpsProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class EmployeeTrackingController extends Controller
{
    public function index(): View
    {
        return view('pages.employee_tracking.index', [
            'employees' => User::query()
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'mapSettings' => $this->mapSettings(),
        ]);
    }

    public function debugReport(): View
    {
        return view('pages.employee_tracking.debug_report', [
            'employees' => User::query()
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function debugReportData(Request $request): JsonResponse
    {
        $request->merge(['gps_debug' => true]);

        return $this->getTimeLineAjax($request);
    }

    public function liveMap(): View
    {
        return view('pages.employee_tracking.live_location', [
            'mapSettings' => $this->mapSettings(),
        ]);
    }

    public function liveLocations(): JsonResponse
    {
        return response()->json(['data' => $this->liveLocationItems()]);
    }

    public function liveLocationAjax(): JsonResponse
    {
        return response()->json($this->liveLocationItems());
    }

    private function liveLocationItems()
    {
        $currentUserId = auth()->id();
        $onlineThresholdSeconds = $this->onlineThresholdSeconds();

        return EmployeeDevice::query()
            ->with('employee:id,name,email,phone,status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($currentUserId, fn ($query) => $query->where('employee_id', '!=', $currentUserId))
            ->latest('last_seen_at')
            ->get()
            ->map(function (EmployeeDevice $device) use ($onlineThresholdSeconds) {
                $onlineStatus = $this->isDeviceOnline($device, $onlineThresholdSeconds) ? 'online' : 'offline';

                return [
                    'employee_id' => $device->employee_id,
                    'id' => $device->employee_id,
                    'employee_name' => $device->employee?->name ?? 'Unknown',
                    'name' => $device->employee?->name ?? 'Unknown',
                    'email' => $device->employee?->email,
                    'phone' => $device->employee?->phone,
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'latitude' => (float) $device->latitude,
                    'longitude' => (float) $device->longitude,
                    'accuracy' => $device->accuracy !== null ? (float) $device->accuracy : null,
                    'speed' => $device->speed !== null ? (float) $device->speed : null,
                    'bearing' => $device->bearing !== null ? (float) $device->bearing : null,
                    'activity' => $device->activity,
                    'is_gps_on' => (bool) $device->is_gps_on,
                    'is_mock_location' => (bool) $device->is_mock_location,
                    'battery_percentage' => $device->battery_percentage,
                    'last_seen_at' => $device->last_seen_at?->toISOString(),
                    'online_status' => $onlineStatus,
                    'status' => $onlineStatus,
                    'updatedAt' => $device->last_seen_at?->diffForHumans(),
                ];
            })
            ->values();
    }

    public function cardView(): View
    {
        return view('pages.employee_tracking.card_view', [
            'cards' => $this->trackingCardItems(),
        ]);
    }

    public function cardViewData(): JsonResponse
    {
        return response()->json($this->trackingCardItems());
    }

    public function getTimeLineAjax(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $employee = User::query()->findOrFail((int) $validated['userId']);
        $date = $validated['date'];
        [$timelineStart, $timelineEnd] = $this->timelineDateBounds($date);

        $attendances = $this->timelineAttendances($employee, $date, $timelineStart, $timelineEnd);
        $attendance = $attendances->last();

        $device = EmployeeDevice::query()
            ->where('employee_id', $employee->id)
            ->latest('last_seen_at')
            ->first();

        $trackings = $this->timelineTrackings($attendances, $timelineStart, $timelineEnd);

        if ($attendances->isEmpty()) {
            $response = [
                'employeeName' => $employee->name,
                'employeeId' => $employee->id,
                'attendanceId' => null,
                'attendanceIds' => [],
                'attendances' => [],
                'attendanceSessions' => [],
                'totalTrackedTime' => '00:00:00',
                'totalAttendanceTime' => '00:00:00',
                'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
                'totalKM' => 0,
                'gpsDistanceKm' => 0,
                'directionsDistanceKm' => null,
                'polylinePoints' => [],
                'polylineSegments' => [],
                'directionsSegments' => [],
                'routeBlocks' => [],
                'timelineEvents' => [],
                'timeLineItems' => [],
            ];

            if ($request->boolean('gps_debug')) {
                $response['gpsDebug'] = $this->timelineDebugPayload($request, $trackings, collect(), []);
            }

            return response()->json($response);
        }

        $timeline = app(EmployeeTimelineBuilder::class)->build($trackings, $this->timelineGpsOptions());
        $timeLineItems = $timeline['items'];
        $totalTrackedSeconds = $this->trackedSecondsByAttendance($trackings);

        $attendanceSeconds = $this->attendanceSeconds($attendances);
        $timelineEvents = $this->groupTimelineEvents($timeLineItems);
        $trackingHealth = $this->trackingHealthPayload($attendances, $trackings, $timeline);

        $response = [
            'employeeId' => $employee->id,
            'employeeName' => $employee->name,
            'attendanceId' => $attendance?->id,
            'attendanceIds' => $attendances->pluck('id')->values(),
            'attendances' => $attendances->map(fn (Attendance $attendance): array => $this->attendancePayload($attendance))->values(),
            'attendanceSessions' => $this->attendanceSessionPayloads($attendances, $timeline),
            'totalTrackedTime' => $this->formatSecondsAsClock($totalTrackedSeconds),
            'totalAttendanceTime' => $this->formatSecondsAsClock($attendanceSeconds),
            'trackingHealth' => $trackingHealth,
            'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
            'totalKM' => $timeline['totalKM'],
            'gpsDistanceKm' => $timeline['gpsDistanceKm'] ?? $timeline['totalKM'],
            'directionsDistanceKm' => $timeline['directionsDistanceKm'] ?? null,
            'polylinePoints' => $timeline['polylinePoints'],
            'polylineSegments' => $timeline['polylineSegments'],
            'directionsSegments' => $timeline['directionsSegments'],
            'routeBlocks' => $timeline['routeBlocks'] ?? [],
            'timelineEvents' => $timelineEvents,
            'timeLineItems' => $timeLineItems,
        ];

        if ($request->boolean('gps_debug')) {
            $response['gpsDebug'] = $this->timelineDebugPayload($request, $trackings, $timeLineItems, $response['polylineSegments'], $timeline);
        }

        return response()->json($response);
    }

    public function timeline(Request $request, User $employee): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'];
        [$timelineStart, $timelineEnd] = $this->timelineDateBounds($date);

        $attendances = $this->timelineAttendances($employee, $date, $timelineStart, $timelineEnd);
        $attendance = $attendances->last();

        $trackings = $this->timelineTrackings($attendances, $timelineStart, $timelineEnd);

        $timeline = app(EmployeeTimelineBuilder::class)->build($trackings, $this->timelineGpsOptions());

        $items = $timeline['items']
            ->map(function (array $tracking) {

                return [
                    ...$tracking,
                    'attendance_id' => $tracking['attendanceId'] ?? null,
                    'tracking_type' => $tracking['trackingType'] ?? null,
                    'type_label' => ucfirst((string) ($tracking['trackingType'] ?? $tracking['type'] ?? '')),
                    'start_time' => $tracking['startTime'] ?? null,
                    'end_time' => $tracking['endTime'] ?? $tracking['startTime'] ?? null,
                    'elapsed_seconds' => 0,
                ];
            })
            ->values();

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
            ],
            'attendance' => $attendance ? [
                'id' => $attendance->id,
                'attendance_date' => $attendance->attendance_date?->toDateString(),
                'check_in_at' => $attendance->check_in_at?->toISOString(),
                'check_out_at' => $attendance->check_out_at?->toISOString(),
                'worked_minutes' => $attendance->worked_minutes,
                'status' => $attendance->status,
            ] : null,
            'attendance_ids' => $attendances->pluck('id')->values(),
            'attendance_sessions' => $this->attendanceSessionPayloads($attendances, $timeline),
            'summary' => [
                'points_count' => $items->count(),
                'raw_points_count' => $trackings->count(),
                'total_tracked_seconds' => 0,
                'total_attendance_minutes' => $attendance?->worked_minutes ?? null,
            ],
            'trackings' => $items,
            'polyline_points' => $timeline['polylinePoints'],
            'polyline_segments' => $timeline['polylineSegments'],
            'route_blocks' => $timeline['routeBlocks'] ?? [],
            'directions_segments' => $timeline['directionsSegments'] ?? [],
            'gps_distance_km' => $timeline['gpsDistanceKm'] ?? $timeline['totalKM'],
            'directions_distance_km' => $timeline['directionsDistanceKm'] ?? null,
        ]);
    }

    public function snapTimeLineRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => ['required', 'array', 'min:2', 'max:300'],
            'points.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'points.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $googleMapsKey = (string) $this->settingValue(
            'google_maps_api_key',
            config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY', ''))
        );

        if ($googleMapsKey === '') {
            return response()->json(['snapped' => false, 'points' => []]);
        }

        $snappedPoints = $this->snapPointsToRoads($validated['points'], $googleMapsKey);

        return response()->json([
            'snapped' => count($snappedPoints) >= 2,
            'points' => $snappedPoints,
        ]);
    }

    private function mapSettings(): array
    {
        return [
            'center_latitude' => (float) $this->settingValue('map_center_latitude', 20.5937),
            'center_longitude' => (float) $this->settingValue('map_center_longitude', 78.9629),
            'zoom_level' => (int) $this->settingValue('map_zoom_level', 5),
            'map_provider' => $this->settingValue('map_provider', 'google'),
            'distance_unit' => $this->settingValue('distance_unit', 'km'),
            'default_route_mode' => $this->settingValue('default_route_mode', 'actual'),
            'actual_gps_route_enabled' => (bool) $this->settingValue('actual_gps_route_enabled', true),
            'road_route_enabled' => (bool) $this->settingValue('road_route_enabled', true),
            'show_offline_points' => (bool) $this->settingValue('show_offline_points', true),
            'show_low_signal_points' => (bool) $this->settingValue('show_low_signal_points', true),
            'show_gaps' => (bool) $this->settingValue('show_gaps', true),
            'low_signal_threshold' => (int) $this->settingValue('low_signal_threshold', 2),
            'google_maps_api_key' => (string) $this->settingValue(
                'google_maps_api_key',
                config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY', ''))
            ),
        ];
    }

    private function trackingCardItems()
    {
        $onlineThresholdSeconds = $this->onlineThresholdSeconds();

        $todayAttendances = Attendance::query()
            ->with('user:id,name,email,phone,designation,status')
            ->whereDate('attendance_date', now()->toDateString())
            ->latest('check_in_at')
            ->get()
            ->unique('user_id');

        $devicesByUser = EmployeeDevice::query()
            ->whereIn('employee_id', $todayAttendances->pluck('user_id'))
            ->latest('last_seen_at')
            ->get()
            ->unique('employee_id')
            ->keyBy('employee_id');

        return $todayAttendances
            ->map(function (Attendance $attendance) use ($devicesByUser, $onlineThresholdSeconds) {
                $device = $devicesByUser->get($attendance->user_id);
                $isOnline = $device && $this->isDeviceOnline($device, $onlineThresholdSeconds);

                return [
                    'id' => $attendance->user_id,
                    'name' => $attendance->user?->name ?? 'Unknown',
                    'phoneNumber' => $attendance->user?->phone,
                    'designation' => $attendance->user?->designation,
                    'batteryLevel' => $device?->battery_percentage,
                    'isGpsOn' => (bool) ($device?->is_gps_on ?? false),
                    'isWifiOn' => null,
                    'updatedAt' => $device?->last_seen_at?->diffForHumans(),
                    'isOnline' => (bool) $isOnline,
                    'attendanceInAt' => $attendance->check_in_at?->format('h:i A') ?? '',
                    'attendanceOutAt' => $attendance->check_out_at?->format('h:i A') ?? '',
                    'latitude' => $device?->latitude !== null ? (float) $device->latitude : null,
                    'longitude' => $device?->longitude !== null ? (float) $device->longitude : null,
                    'accuracy' => $device?->accuracy !== null ? (float) $device->accuracy : null,
                    'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
                    'timelineUrl' => route('timeLine', [
                        'employee' => $attendance->user_id,
                        'date' => $attendance->attendance_date?->toDateString(),
                    ]),
                ];
            })
            ->values();
    }

    private function onlineThresholdSeconds(): int
    {
        return max(60, (int) $this->settingValue(
            'online_threshold_seconds',
            $this->secondsFromSetting(
                (int) $this->settingValue('offline_check_time', 15),
                (string) $this->settingValue('offline_check_time_type', 'minutes')
            )
        ));
    }

    private function isDeviceOnline(EmployeeDevice $device, int $thresholdSeconds): bool
    {
        return $device->last_seen_at
            && $device->last_seen_at->gt(now()->subSeconds($thresholdSeconds));
    }

    private function timelineModuleItems($trackings)
    {
        $filteredTrackings = $this->filterTimelineTrackings($trackings);

        return collect($filteredTrackings)
            ->map(function (LocationTracking $tracking, int $index) use ($filteredTrackings) {
                $previousTracking = $filteredTrackings[$index - 1] ?? null;
                $nextTracking = $filteredTrackings[$index + 1] ?? null;
                $type = $this->timelineModuleType($tracking, $previousTracking, $nextTracking);
                $nextType = $nextTracking ? $this->timelineModuleType($nextTracking, $tracking, $filteredTrackings[$index + 2] ?? null) : null;
                $distance = $nextTracking
                    && $this->isTimelineMovementType($type)
                    && $this->isTimelineMovementType($nextType)
                    && ! $this->shouldBreakTimelineSegment($tracking, $nextTracking)
                    ? $this->distanceInKm(
                        (float) $tracking->latitude,
                        (float) $tracking->longitude,
                        (float) $nextTracking->latitude,
                        (float) $nextTracking->longitude
                    )
                    : 0;

                return [
                    'id' => $tracking->id,
                    'type' => $type,
                    'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
                    'bearing' => $tracking->bearing !== null ? (float) $tracking->bearing : null,
                    'activity' => $tracking->activity,
                    'batteryPercentage' => $tracking->battery_percentage,
                    'isGPSOn' => (bool) $tracking->is_gps_on,
                    'isWifiOn' => (bool) ($tracking->is_wifi_on ?? false),
                    'isOffline' => (bool) ($tracking->is_offline ?? false),
                    'latitude' => (float) $tracking->latitude,
                    'longitude' => (float) $tracking->longitude,
                    'address' => null,
                    'signalStrength' => $tracking->signal_strength,
                    'trackingType' => $tracking->type,
                    'segmentBreakBefore' => $previousTracking ? $this->shouldBreakTimelineSegment($previousTracking, $tracking) : false,
                    'startTime' => $this->trackingTime($tracking)?->format('h:i A'),
                    'endTime' => ($nextTracking ? $this->trackingTime($nextTracking) : null)?->format('h:i A')
                        ?? $this->trackingTime($tracking)?->format('h:i A'),
                    'elapseTime' => $nextTracking && $this->trackingTime($tracking) && $this->trackingTime($nextTracking)
                        ? $this->formatSecondsAsClock($this->trackingTime($tracking)->diffInSeconds($this->trackingTime($nextTracking)))
                        : '00:00:00',
                    'distance' => round($distance, 2),
                ];
            })
            ->values();
    }

    private function filterTimelineTrackings($trackings): array
    {
        return app(TimelineGpsProcessor::class)->filter($trackings, $this->timelineGpsOptions());
    }

    private function timelineDebugPayload(Request $request, $rawTrackings, $timeLineItems, array $polylineSegments, ?array $timeline = null): array
    {
        if ($timeline && isset($timeline['diagnostics'], $timeline['rejectionReasons'])) {
            $diagnostics = collect($timeline['diagnostics']);

            return [
                'endpoint_url' => route('dashboard.getTimeLineAjax'),
                'request' => $request->only(['userId', 'date']),
                'settings' => $timeline['settings'] ?? app(GpsTrackingValidationService::class)->settings(),
                'raw_point_count' => $rawTrackings->count(),
                'validated_point_count' => $diagnostics->where('accepted', true)->count(),
                'timeline_item_count' => $timeLineItems->count(),
                'rejected_point_count' => $diagnostics->where('accepted', false)->count(),
                'rejection_reason_count' => $timeline['rejectionReasons'],
                'segment_count' => count($polylineSegments),
                'directions_segment_count' => count($timeline['directionsSegments'] ?? []),
                'directions_segments' => $timeline['directionsSegments'] ?? [],
                'directions_waypoint_count' => collect($timeline['directionsSegments'] ?? [])
                    ->map(fn (array $segment): int => count($segment['waypoints'] ?? []))
                    ->values()
                    ->all(),
                'gps_distance_km' => $timeline['totalKM'] ?? 0,
                'directions_distance_km' => null,
                'polyline_segments' => $polylineSegments,
                'polyline_points' => collect($polylineSegments)->pluck('points')->flatten(1)->values()->all(),
                'raw_point_diagnostics' => $timeline['diagnostics'],
            ];
        }

        $validator = app(GpsTrackingValidationService::class);
        $options = $this->timelineGpsOptions();
        $settings = $validator->settings([
            'gps_min_distance_metres' => $options['minimum_distance_meters'],
            'gps_max_accuracy_metres' => $options['max_accuracy_meters'],
            'gps_max_speed_mps' => $options['max_computed_speed_kmh'] / 3.6,
            'gps_max_bearing_change_degrees' => $options['max_bearing_change_degrees'],
            'gps_bearing_min_distance_metres' => $options['bearing_drift_distance_meters'],
        ]);

        $accepted = [];
        $rejectionReasons = [];
        $rawPointDiagnostics = [];

        foreach ($rawTrackings as $tracking) {
            $previous = $accepted[count($accepted) - 1] ?? null;
            $previousPrevious = $accepted[count($accepted) - 2] ?? null;
            $result = $validator->validateWithSettings($tracking, $previous, $previousPrevious, $settings);

            if ($result['accepted']) {
                $accepted[] = $tracking;
            } else {
                $reason = (string) ($result['reason'] ?? 'unknown');
                $rejectionReasons[$reason] = ($rejectionReasons[$reason] ?? 0) + 1;
            }

            $rawPointDiagnostics[] = [
                'id' => $tracking->id,
                'accepted' => (bool) $result['accepted'],
                'reason' => $result['reason'],
                'recorded_at' => $this->trackingTime($tracking)?->toDateTimeString(),
                'attendance_id' => $tracking->attendance_id,
                'activity' => $tracking->activity,
                'type' => $tracking->type,
                'latitude' => (float) $tracking->latitude,
                'longitude' => (float) $tracking->longitude,
                'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
                'speed' => $tracking->speed !== null ? (float) $tracking->speed : null,
                'distance_metres' => $result['distance_metres'],
                'time_difference_seconds' => $result['time_difference_seconds'],
                'speed_mps' => $result['speed_mps'],
                'bearing' => $result['bearing'],
                'bearing_difference' => $result['bearing_difference'],
            ];
        }

        return [
            'endpoint_url' => route('dashboard.getTimeLineAjax'),
            'request' => $request->only(['userId', 'date']),
            'settings' => $settings,
            'raw_point_count' => $rawTrackings->count(),
            'validated_point_count' => count($accepted),
            'timeline_item_count' => $timeLineItems->count(),
            'rejected_point_count' => max(0, $rawTrackings->count() - count($accepted)),
            'rejection_reason_count' => $rejectionReasons,
            'segment_count' => count($polylineSegments),
            'directions_segment_count' => 0,
            'directions_segments' => [],
            'directions_waypoint_count' => [],
            'gps_distance_km' => round((float) collect($polylineSegments)->sum('distance_km'), 2),
            'directions_distance_km' => null,
            'polyline_segments' => $polylineSegments,
            'polyline_points' => collect($polylineSegments)->pluck('points')->flatten(1)->values()->all(),
            'raw_point_diagnostics' => $rawPointDiagnostics,
        ];
    }

    private function trackedSecondsByAttendance($trackings): int
    {
        return (int) $trackings
            ->groupBy('attendance_id')
            ->sum(function ($sessionTrackings): int {
                $ordered = $sessionTrackings->values();

                return (int) $ordered
                    ->map(function (LocationTracking $tracking, int $index) use ($ordered): int {
                        $nextTracking = $ordered->get($index + 1);

                        $trackingTime = $this->trackingTime($tracking);
                        $nextTrackingTime = $nextTracking ? $this->trackingTime($nextTracking) : null;

                        return $trackingTime && $nextTrackingTime
                            ? $trackingTime->diffInSeconds($nextTrackingTime)
                            : 0;
                    })
                    ->sum();
            });
    }

    private function attendanceSeconds($attendances): int
    {
        return (int) $attendances->sum(function (Attendance $attendance): int {
            return $attendance->check_in_at
                ? $attendance->check_in_at->diffInSeconds($attendance->check_out_at ?? now())
                : 0;
        });
    }

    private function trackingHealthPayload($attendances, $trackings, ?array $timeline = null): array
    {
        $intervalSeconds = $this->trackingIntervalSeconds();
        $gapThresholdSeconds = max($intervalSeconds * 3, $this->largeGapSeconds());
        $attendanceSeconds = $this->attendanceSeconds($attendances);
        $savedRows = $trackings->count();
        $expectedUpdates = $attendanceSeconds > 0 ? ((int) floor($attendanceSeconds / $intervalSeconds) + 1) : 0;
        $trackingSpanSeconds = $this->trackingSpanSeconds($trackings);
        $missingSeconds = max(0, $attendanceSeconds - $trackingSpanSeconds);
        $gaps = $this->trackingGapReport($trackings, $gapThresholdSeconds);
        $lowSignalThreshold = (int) $this->settingValue('low_signal_threshold', 2);
        $lowSignalRows = $trackings->filter(fn (LocationTracking $tracking): bool => $this->signalValue($tracking->signal_strength) !== null
            && $this->signalValue($tracking->signal_strength) <= $lowSignalThreshold);
        $offlineRows = $trackings->filter(fn (LocationTracking $tracking): bool => (bool) ($tracking->is_offline ?? false));
        $accuracyValues = $trackings
            ->pluck('accuracy')
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): float => (float) $value);
        $batteryValues = $trackings
            ->pluck('battery_percentage')
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): int => (int) $value)
            ->values();
        $signalValues = $trackings
            ->map(fn (LocationTracking $tracking): ?int => $this->signalValue($tracking->signal_strength))
            ->filter(fn (?int $value): bool => $value !== null)
            ->values();
        $routePointsCount = (int) collect($timeline['polylineSegments'] ?? [])
            ->pluck('points')
            ->flatten(1)
            ->count();
        $mockRows = $trackings->filter(fn (LocationTracking $tracking): bool => (bool) ($tracking->is_mock_location ?? false));
        $gpsOffRows = $trackings->filter(fn (LocationTracking $tracking): bool => ! (bool) ($tracking->is_gps_on ?? true));
        $wifiOnRows = $trackings->filter(fn (LocationTracking $tracking): bool => (bool) ($tracking->is_wifi_on ?? false));
        $lastMobileSyncAt = $trackings
            ->map(fn (LocationTracking $tracking) => $tracking->created_at)
            ->filter()
            ->max();
        $firstTracking = $trackings->first();
        $lastTracking = $trackings->last();
        $firstTrackingAt = $firstTracking ? $this->trackingTime($firstTracking) : null;
        $lastTrackingAt = $lastTracking ? $this->trackingTime($lastTracking) : null;
        $firstCheckInAt = $attendances
            ->map(fn (Attendance $attendance) => $attendance->check_in_at)
            ->filter()
            ->sort()
            ->first();
        $firstTrackingDelaySeconds = $firstCheckInAt && $firstTrackingAt
            ? max(0, $firstCheckInAt->diffInSeconds($firstTrackingAt))
            : null;
        $lateTrackingThresholdSeconds = max(120, $intervalSeconds * 2);

        return [
            'tracking_interval_seconds' => $intervalSeconds,
            'gap_threshold_seconds' => $gapThresholdSeconds,
            'expected_updates' => $expectedUpdates,
            'successful_updates' => $savedRows,
            'saved_points_count' => $savedRows,
            'route_points_count' => $routePointsCount,
            'route_segments_count' => count($timeline['polylineSegments'] ?? []),
            'offline_synced_points_count' => $offlineRows->count(),
            'online_points_count' => max(0, $savedRows - $offlineRows->count()),
            'low_signal_points_count' => $lowSignalRows->count(),
            'low_signal_threshold' => $lowSignalThreshold,
            'accuracy_min' => $accuracyValues->isNotEmpty() ? round($accuracyValues->min(), 2) : null,
            'accuracy_max' => $accuracyValues->isNotEmpty() ? round($accuracyValues->max(), 2) : null,
            'accuracy_avg' => $accuracyValues->isNotEmpty() ? round($accuracyValues->avg(), 2) : null,
            'battery_start' => $batteryValues->first(),
            'battery_end' => $batteryValues->last(),
            'signal_min' => $signalValues->isNotEmpty() ? $signalValues->min() : null,
            'signal_max' => $signalValues->isNotEmpty() ? $signalValues->max() : null,
            'mock_points_count' => $mockRows->count(),
            'gps_off_points_count' => $gpsOffRows->count(),
            'wifi_on_points_count' => $wifiOnRows->count(),
            'last_mobile_sync_at' => $lastMobileSyncAt
                ? Carbon::parse($lastMobileSyncAt)->toDateTimeString()
                : null,
            'first_check_in_at' => $firstCheckInAt?->toDateTimeString(),
            'first_tracking_at' => $firstTrackingAt?->toDateTimeString(),
            'last_tracking_at' => $lastTrackingAt?->toDateTimeString(),
            'first_tracking_delay_seconds' => $firstTrackingDelaySeconds,
            'first_tracking_delay_duration' => $firstTrackingDelaySeconds !== null
                ? $this->formatSecondsAsClock($firstTrackingDelaySeconds)
                : null,
            'tracking_started_late' => $firstTrackingDelaySeconds !== null && $firstTrackingDelaySeconds > $lateTrackingThresholdSeconds,
            'late_tracking_threshold_seconds' => $lateTrackingThresholdSeconds,
            'ignored_points_count' => (int) collect($timeline['rejectionReasons'] ?? [])->sum(),
            'ignored_reasons' => $timeline['rejectionReasons'] ?? [],
            'tracking_coverage_percentage' => $expectedUpdates > 0 ? round(($savedRows / $expectedUpdates) * 100, 2) : 0,
            'attendance_seconds' => $attendanceSeconds,
            'attendance_duration' => $this->formatSecondsAsClock($attendanceSeconds),
            'saved_tracking_span_seconds' => $trackingSpanSeconds,
            'saved_tracking_span' => $this->formatSecondsAsClock($trackingSpanSeconds),
            'missing_tracking_seconds' => $missingSeconds,
            'missing_tracking_duration' => $this->formatSecondsAsClock($missingSeconds),
            'gap_count' => count($gaps),
            'largest_gaps' => array_slice($gaps, 0, 10),
        ];
    }

    private function signalValue(mixed $signalStrength): ?int
    {
        if ($signalStrength === null || $signalStrength === '') {
            return null;
        }

        if (is_numeric($signalStrength)) {
            return (int) $signalStrength;
        }

        if (preg_match('/-?\d+/', (string) $signalStrength, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    private function trackingSpanSeconds($trackings): int
    {
        $ordered = $trackings->values();
        $first = $ordered->first();
        $last = $ordered->last();

        $firstTime = $first ? $this->trackingTime($first) : null;
        $lastTime = $last ? $this->trackingTime($last) : null;

        if (! $firstTime || ! $lastTime) {
            return 0;
        }

        return (int) $firstTime->diffInSeconds($lastTime);
    }

    private function trackingGapReport($trackings, int $gapThresholdSeconds): array
    {
        $gaps = [];
        $ordered = $trackings->values();

        for ($index = 1; $index < $ordered->count(); $index++) {
            $previous = $ordered->get($index - 1);
            $current = $ordered->get($index);

            $previousTime = $previous ? $this->trackingTime($previous) : null;
            $currentTime = $current ? $this->trackingTime($current) : null;

            if (! $previousTime || ! $currentTime) {
                continue;
            }

            $seconds = $previousTime->diffInSeconds($currentTime);
            if ($seconds < $gapThresholdSeconds) {
                continue;
            }

            $distanceKm = $this->distanceInKm(
                (float) $previous->latitude,
                (float) $previous->longitude,
                (float) $current->latitude,
                (float) $current->longitude
            );

            $gaps[] = [
                'previous_tracking_id' => $previous->id,
                'current_tracking_id' => $current->id,
                'previous_recorded_at' => $previousTime->toDateTimeString(),
                'current_recorded_at' => $currentTime->toDateTimeString(),
                'gap_seconds' => $seconds,
                'gap_minutes' => round($seconds / 60, 2),
                'previous_coordinate' => [
                    'latitude' => (float) $previous->latitude,
                    'longitude' => (float) $previous->longitude,
                ],
                'current_coordinate' => [
                    'latitude' => (float) $current->latitude,
                    'longitude' => (float) $current->longitude,
                ],
                'distance_km' => round($distanceKm, 2),
                'reason' => 'missing_periodic_updates',
            ];
        }

        usort($gaps, fn (array $a, array $b): int => $b['gap_seconds'] <=> $a['gap_seconds']);

        return $gaps;
    }

    private function attendancePayload(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toISOString(),
            'check_out_at' => $attendance->check_out_at?->toISOString(),
            'check_in_time' => $attendance->check_in_at?->format('h:i A'),
            'check_out_time' => $attendance->check_out_at?->format('h:i A'),
            'worked_minutes' => $attendance->worked_minutes,
            'status' => $attendance->status,
        ];
    }

    private function attendanceSessionPayloads($attendances, array $timeline): array
    {
        $routeBlocks = collect($timeline['routeBlocks'] ?? [])->groupBy('attendance_id');
        $items = collect($timeline['items'] ?? [])->groupBy('attendanceId');

        return $attendances
            ->values()
            ->map(function (Attendance $attendance, int $index) use ($routeBlocks, $items): array {
                $sessionItems = $items->get($attendance->id, collect());

                return [
                    'attendance_id' => $attendance->id,
                    'session_index' => $index + 1,
                    'attendance' => $this->attendancePayload($attendance),
                    'item_count' => $sessionItems->count(),
                    'route_blocks' => $routeBlocks->get($attendance->id, collect())->values()->all(),
                ];
            })
            ->all();
    }

    private function groupTimelineEvents($items): array
    {
        $events = [];
        $current = null;

        foreach (collect($items)->values() as $item) {
            $eventType = $this->timelineEventType($item['type'] ?? null);
            $attendanceId = $item['attendanceId'] ?? null;

            if (! $current
                || $current['type'] !== $eventType
                || $current['attendance_id'] !== $attendanceId
                || ($item['segmentBreakBefore'] ?? false) === true
                || in_array($eventType, ['checkIn', 'checkOut', 'proofPost'], true)) {
                if ($current) {
                    $events[] = $this->finalizeTimelineEvent($current);
                }

                $current = $this->newTimelineEvent($item, $eventType);
                continue;
            }

            $previousLat = $current['end_latitude'];
            $previousLng = $current['end_longitude'];
            $currentLat = $item['latitude'] ?? null;
            $currentLng = $item['longitude'] ?? null;

            $current['items'][] = $item;
            $current['end_time'] = $item['endTime'] ?? $item['startTime'] ?? $current['end_time'];
            $current['end_latitude'] = $currentLat ?? $current['end_latitude'];
            $current['end_longitude'] = $currentLng ?? $current['end_longitude'];
            $current['distance'] += $this->eventDistanceKm($eventType, $previousLat, $previousLng, $currentLat, $currentLng);
            $current['accuracies'][] = $item['accuracy'] ?? null;
            $current['battery_percentage'] = $item['batteryPercentage'] ?? $current['battery_percentage'];
            $current['is_gps_on'] = $item['isGPSOn'] ?? $current['is_gps_on'];
            $current['is_wifi_on'] = $item['isWifiOn'] ?? $current['is_wifi_on'];
        }

        if ($current) {
            $events[] = $this->finalizeTimelineEvent($current);
        }

        return $events;
    }

    private function timelineEventType(?string $type): string
    {
        return match ($type) {
            'vehicle' => 'travelling',
            'walk' => 'walk',
            'checkIn' => 'checkIn',
            'checkOut' => 'checkOut',
            'proofPost' => 'proofPost',
            default => 'still',
        };
    }

    private function newTimelineEvent(array $item, string $eventType): array
    {
        return [
            'attendance_id' => $item['attendanceId'] ?? null,
            'type' => $eventType,
            'activity' => $item['activity'] ?? null,
            'tracking_type' => $item['trackingType'] ?? null,
            'start_time' => $item['startTime'] ?? null,
            'end_time' => $item['endTime'] ?? $item['startTime'] ?? null,
            'start_latitude' => $item['latitude'] ?? null,
            'start_longitude' => $item['longitude'] ?? null,
            'end_latitude' => $item['latitude'] ?? null,
            'end_longitude' => $item['longitude'] ?? null,
            'distance' => (float) ($item['distance'] ?? 0),
            'accuracies' => [$item['accuracy'] ?? null],
            'battery_percentage' => $item['batteryPercentage'] ?? null,
            'is_gps_on' => $item['isGPSOn'] ?? null,
            'is_wifi_on' => $item['isWifiOn'] ?? null,
            'address' => $item['address'] ?? null,
            'items' => [$item],
        ];
    }

    private function finalizeTimelineEvent(array $event): array
    {
        $accuracies = collect($event['accuracies'])
            ->filter(fn ($accuracy) => $accuracy !== null && $accuracy !== '')
            ->map(fn ($accuracy) => (float) $accuracy)
            ->values();

        return [
            'attendance_id' => $event['attendance_id'],
            'type' => $event['type'],
            'activity' => $event['activity'],
            'tracking_type' => $event['tracking_type'],
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
            'duration' => $this->eventDuration($event['start_time'], $event['end_time']),
            'start_latitude' => $event['start_latitude'],
            'start_longitude' => $event['start_longitude'],
            'end_latitude' => $event['end_latitude'],
            'end_longitude' => $event['end_longitude'],
            'distance' => round((float) $event['distance'], 2),
            'accuracy_min' => $accuracies->isNotEmpty() ? $accuracies->min() : null,
            'accuracy_max' => $accuracies->isNotEmpty() ? $accuracies->max() : null,
            'battery_percentage' => $event['battery_percentage'],
            'is_gps_on' => $event['is_gps_on'],
            'is_wifi_on' => $event['is_wifi_on'],
            'address' => $event['address'],
            'item_count' => count($event['items']),
        ];
    }

    private function eventDistanceKm(string $eventType, mixed $fromLat, mixed $fromLng, mixed $toLat, mixed $toLng): float
    {
        if (! in_array($eventType, ['travelling', 'walk'], true)
            || $fromLat === null
            || $fromLng === null
            || $toLat === null
            || $toLng === null) {
            return 0.0;
        }

        return $this->distanceInKm((float) $fromLat, (float) $fromLng, (float) $toLat, (float) $toLng);
    }

    private function eventDuration(?string $startTime, ?string $endTime): string
    {
        if (! $startTime || ! $endTime) {
            return '00:00:00';
        }

        try {
            $start = \Illuminate\Support\Carbon::createFromFormat('h:i A', $startTime);
            $end = \Illuminate\Support\Carbon::createFromFormat('h:i A', $endTime);

            return $this->formatSecondsAsClock(max(0, $start->diffInSeconds($end)));
        } catch (\Throwable) {
            return '00:00:00';
        }
    }

    private function isUnrealisticTimelineJump(LocationTracking $previous, LocationTracking $current, float $distanceKm): bool
    {
        $previousTime = $this->trackingTime($previous);
        $currentTime = $this->trackingTime($current);

        if (! $previousTime || ! $currentTime) {
            return false;
        }

        $seconds = max(1, $previousTime->diffInSeconds($currentTime));
        $speedKmh = ($distanceKm / $seconds) * 3600;

        return $speedKmh > 120;
    }

    private function shouldBreakTimelineSegment(LocationTracking $previous, LocationTracking $current): bool
    {
        if ($previous->attendance_id !== $current->attendance_id) {
            return true;
        }

        $previousTime = $this->trackingTime($previous);
        $currentTime = $this->trackingTime($current);

        if (! $previousTime || ! $currentTime) {
            return false;
        }

        $distanceKm = $this->distanceInKm(
            (float) $previous->latitude,
            (float) $previous->longitude,
            (float) $current->latitude,
            (float) $current->longitude
        );
        $seconds = max(1, $previousTime->diffInSeconds($currentTime));
        $speedKmh = ($distanceKm / $seconds) * 3600;

        $intervalSeconds = $this->trackingIntervalSeconds();
        $gapThresholdSeconds = max($intervalSeconds * 3, $this->largeGapSeconds());
        $largeGapDistanceKm = ((float) $this->settingValue('large_gap_distance_meters', 2000)) / 1000;

        return $seconds >= $gapThresholdSeconds
            || $distanceKm > $largeGapDistanceKm
            || $speedKmh > ((float) $this->settingValue('maximum_speed_kmph', (float) $this->settingValue('gps_max_speed_mps', 25) * 3.6));
    }

    private function snapPointsToRoads(array $points, string $googleMapsKey): array
    {
        $snappedPoints = [];
        $count = count($points);

        for ($offset = 0; $offset < $count - 1; $offset += 99) {
            $chunk = array_slice($points, $offset, 100);

            if (count($chunk) < 2) {
                continue;
            }

            $path = collect($chunk)
                ->map(fn (array $point) => $point['lat'] . ',' . $point['lng'])
                ->implode('|');

            $response = Http::timeout(10)->get('https://roads.googleapis.com/v1/snapToRoads', [
                'path' => $path,
                'interpolate' => 'true',
                'key' => $googleMapsKey,
            ]);

            if (! $response->ok()) {
                return [];
            }

            foreach ($response->json('snappedPoints', []) as $snappedPoint) {
                $location = $snappedPoint['location'] ?? null;

                if (! isset($location['latitude'], $location['longitude'])) {
                    continue;
                }

                $point = [
                    'lat' => (float) $location['latitude'],
                    'lng' => (float) $location['longitude'],
                ];
                $previous = $snappedPoints[count($snappedPoints) - 1] ?? null;

                if ($previous && $previous['lat'] === $point['lat'] && $previous['lng'] === $point['lng']) {
                    continue;
                }

                $snappedPoints[] = $point;
            }
        }

        return $snappedPoints;
    }

    private function timelineModuleType(LocationTracking $tracking, ?LocationTracking $previous = null, ?LocationTracking $next = null): string
    {
        if ($tracking->type === 'checked_in') {
            return 'checkIn';
        }

        if ($tracking->type === 'checked_out') {
            return 'checkOut';
        }

        if ($this->isStationaryTimelinePoint($tracking, $previous, $next)) {
            return 'still';
        }

        $trackingType = strtolower((string) $tracking->type);
        $activity = strtolower((string) $tracking->activity);

        if (in_array($trackingType, ['travelling', 'vehicle', 'walking', 'walk'], true)) {
            if (in_array($activity, ['activitytype.walking', 'walking', 'walk'], true) || $trackingType === 'walking' || $trackingType === 'walk') {
                return 'walk';
            }
            return 'vehicle';
        }

        if (in_array($activity, ['activitytype.still', 'still'], true)) {
            return 'still';
        }

        if ($this->isStationaryTimelinePoint($tracking, $previous, $next)) {
            return 'still';
        }

        return match (true) {
            in_array($activity, ['activitytype.walking', 'walking', 'walk'], true) => 'walk',
            default => 'vehicle',
        };
    }

    private function isTimelineMovementType(?string $type): bool
    {
        return in_array($type, ['vehicle', 'walk'], true);
    }

    private function isStationaryTimelinePoint(LocationTracking $tracking, ?LocationTracking $previous, ?LocationTracking $next): bool
    {
        $speed = $tracking->speed !== null ? (float) $tracking->speed : null;
        if ($speed !== null && $speed > 1.2) {
            return false;
        }

        if (! $previous && ! $next) {
            return false;
        }

        $nearbyStationary = function (LocationTracking $nearby) use ($tracking): bool {
            $nearbySpeed = $nearby->speed !== null ? (float) $nearby->speed : null;
            if ($nearbySpeed !== null && $nearbySpeed > 1.2) {
                return false;
            }

            $distanceMeters = $this->distanceInKm(
                (float) $tracking->latitude,
                (float) $tracking->longitude,
                (float) $nearby->latitude,
                (float) $nearby->longitude
            ) * 1000;

            return $distanceMeters <= 30;
        };

        if ($previous && $next && $nearbyStationary($previous) && $nearbyStationary($next)) {
            $validator = app(GpsTrackingValidationService::class);
            $incomingBearing = $validator->bearingDegrees(
                (float) $previous->latitude,
                (float) $previous->longitude,
                (float) $tracking->latitude,
                (float) $tracking->longitude,
            );
            $outgoingBearing = $validator->bearingDegrees(
                (float) $tracking->latitude,
                (float) $tracking->longitude,
                (float) $next->latitude,
                (float) $next->longitude,
            );
            $bearingDifference = $validator->bearingDifferenceDegrees($incomingBearing, $outgoingBearing);

            return $bearingDifference >= 45;
        }

        return $speed !== null
            && $speed <= 0.5
            && (($previous && $nearbyStationary($previous)) || ($next && $nearbyStationary($next)));
    }

    private function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return app(TimelineGpsProcessor::class)->distanceInKm($lat1, $lon1, $lat2, $lon2);
    }

    private function timelineGpsOptions(): array
    {
        return [
            'minimum_distance_meters' => (float) $this->settingValue('gps_min_distance_metres', $this->settingValue('minimum_distance_meters', 30)),
            'max_accuracy_meters' => (float) $this->settingValue('gps_max_accuracy_metres', $this->settingValue('minimum_accuracy', 50)),
            'simplify_after_points' => (int) $this->settingValue('timeline_simplify_after_points', 1000),
            'simplification_tolerance_meters' => (float) $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'douglas_peucker_tolerance_meters' => (float) $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'bearing_drift_distance_meters' => (float) $this->settingValue('gps_bearing_min_segment_distance_metres', $this->settingValue('gps_bearing_min_distance_metres', 10)),
            'bearing_change_degrees' => (float) $this->settingValue('timeline_bearing_change_degrees', 60),
            'max_bearing_change_degrees' => (float) $this->settingValue('gps_max_bearing_change_degrees', 45),
            'max_computed_speed_kmh' => (float) $this->settingValue('maximum_speed_kmph', (float) $this->settingValue('gps_max_speed_mps', 25) * 3.6),
            'tracking_interval_seconds' => $this->trackingIntervalSeconds(),
            'gps_max_inactive_gap_seconds' => $this->largeGapSeconds(),
            'large_gap_distance_meters' => (float) $this->settingValue('large_gap_distance_meters', 2000),
        ];
    }

    private function trackingIntervalSeconds(): int
    {
        return max(1, (int) $this->settingValue(
            'tracking_interval_seconds',
            $this->secondsFromSetting(
                (int) $this->settingValue('location_update_interval', 15),
                (string) $this->settingValue('location_update_interval_type', 'seconds')
            )
        ));
    }

    private function largeGapSeconds(): int
    {
        return max(1, (int) $this->settingValue(
            'gps_max_inactive_gap_seconds',
            (int) round(((float) $this->settingValue('large_gap_minutes', 10)) * 60)
        ));
    }

    private function secondsFromSetting(int $value, string $type): int
    {
        return match ($type) {
            'minutes' => $value * 60,
            'hours' => $value * 3600,
            default => $value,
        };
    }

    private function timelineDateBounds(string $date): array
    {
        $timezone = config('app.timezone', 'UTC');
        $start = \Illuminate\Support\Carbon::parse($date, $timezone)->startOfDay();

        return [$start, $start->copy()->endOfDay()];
    }

    private function timelineAttendances(User $employee, string $date, Carbon $timelineStart, Carbon $timelineEnd)
    {
        $start = $timelineStart->toDateTimeString();
        $end = $timelineEnd->toDateTimeString();

        return Attendance::query()
            ->where('user_id', $employee->id)
            ->where(function ($query) use ($date, $start, $end): void {
                $query->whereDate('attendance_date', $date)
                    ->orWhereBetween('check_in_at', [$start, $end])
                    ->orWhereBetween('check_out_at', [$start, $end])
                    ->orWhereExists(function ($subQuery) use ($start, $end): void {
                        $subQuery->selectRaw('1')
                            ->from('location_trackings')
                            ->whereColumn('location_trackings.attendance_id', 'attendances.id')
                            ->whereRaw('COALESCE(location_trackings.recorded_at, location_trackings.created_at) BETWEEN ? AND ?', [$start, $end]);
                    });
            })
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();
    }

    private function timelineTrackings($attendances, Carbon $timelineStart, Carbon $timelineEnd)
    {
        if ($attendances->isEmpty()) {
            return collect();
        }

        return LocationTracking::query()
            ->with('attendance')
            ->whereIn('attendance_id', $attendances->pluck('id'))
            ->whereRaw('COALESCE(recorded_at, created_at) BETWEEN ? AND ?', [
                $timelineStart->toDateTimeString(),
                $timelineEnd->toDateTimeString(),
            ])
            ->orderByRaw('COALESCE(recorded_at, created_at) ASC')
            ->orderBy('id')
            ->get();
    }

    private function polylinePointsFromItems($items, string $latitudeKey = 'latitude', string $longitudeKey = 'longitude')
    {
        return collect($this->polylineSegmentsFromItems($items, $latitudeKey, $longitudeKey))
            ->flatten(1)
            ->values();
    }

    private function polylineSegmentsFromItems($items, string $latitudeKey = 'latitude', string $longitudeKey = 'longitude'): array
    {
        $points = [];
        $segments = [];
        $previous = null;

        foreach ($items as $item) {
            if (! $this->isTimelineMovementType($item['type'] ?? null)) {
                if (count($points) >= 2) {
                    $segments[] = $points;
                }
                $points = [];
                $previous = null;
                continue;
            }

            if (! isset($item[$latitudeKey], $item[$longitudeKey])) {
                continue;
            }

            if (($item['segmentBreakBefore'] ?? false) === true) {
                if (count($points) >= 2) {
                    $segments[] = $points;
                }
                $points = [];
                $previous = null;
            }

            $lat = (float) $item[$latitudeKey];
            $lng = (float) $item[$longitudeKey];

            if ($lat === 0.0 || $lng === 0.0) {
                continue;
            }

            $current = ['lat' => $lat, 'lng' => $lng];

            if ($previous !== null) {
                $distanceMeters = $this->distanceInKm($previous['lat'], $previous['lng'], $lat, $lng) * 1000;
                if ($distanceMeters < 5) {
                    continue;
                }
            }

            $points[] = $current;
            $previous = $current;
        }

        if (count($points) >= 2) {
            $segments[] = $points;
        }

        return $segments;
    }

    private function formatSecondsAsClock(int|float $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }

    private function settingValue(string $key, mixed $default): mixed
    {
        try {
            $setting = AppSetting::query()->where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'float' => (float) $setting->value,
            'json' => json_decode($setting->value, true) ?? $default,
            default => $setting->value,
        };
    }

    private function trackingTypeLabel(LocationTracking $tracking): string
    {
        return match ($tracking->type) {
            'checked_in' => 'Check In',
            'checked_out' => 'Check Out',
            'still' => 'Still',
            'travelling' => 'Travelling',
            default => filled($tracking->activity) ? (string) $tracking->activity : ucfirst((string) $tracking->type),
        };
    }

    private function trackingTime(LocationTracking $tracking): ?Carbon
    {
        return $tracking->recorded_at ?? $tracking->created_at;
    }
}
