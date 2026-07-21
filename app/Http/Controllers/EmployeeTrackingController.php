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

        $attendance = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->latest('check_in_at')
            ->first();

        $device = EmployeeDevice::query()
            ->where('employee_id', $employee->id)
            ->latest('last_seen_at')
            ->first();

        $trackings = LocationTracking::query()
            ->with('attendance')
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($timelineStart, $timelineEnd) {
                $query->whereBetween('recorded_at', [$timelineStart, $timelineEnd])
                    ->orWhere(function ($query) use ($timelineStart, $timelineEnd) {
                        $query->whereNull('recorded_at')
                            ->whereBetween('created_at', [$timelineStart, $timelineEnd]);
                    });
            })
            ->orderByRaw('COALESCE(recorded_at, created_at) ASC')
            ->orderBy('id')
            ->get();

        if (! $attendance && $trackings->isEmpty()) {
            $response = [
                'employeeName' => $employee->name,
                'employeeId' => $employee->id,
                'totalTrackedTime' => '00:00:00',
                'totalAttendanceTime' => '00:00:00',
                'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
                'totalKM' => 0,
                'gpsDistanceKm' => 0,
                'directionsDistanceKm' => null,
                'polylinePoints' => [],
                'polylineSegments' => [],
                'directionsSegments' => [],
                'timeLineItems' => [],
            ];

            if ($request->boolean('gps_debug')) {
                $response['gpsDebug'] = $this->timelineDebugPayload($request, $trackings, collect(), []);
            }

            return response()->json($response);
        }

        $timeline = app(EmployeeTimelineBuilder::class)->build($trackings, $this->timelineGpsOptions());
        $timeLineItems = $timeline['items'];
        $totalTrackedSeconds = $trackings
            ->values()
            ->map(function (LocationTracking $tracking, int $index) use ($trackings) {
                $nextTracking = $trackings->values()->get($index + 1);

                return $nextTracking && $tracking->recorded_at
                    ? $tracking->recorded_at->diffInSeconds($nextTracking->recorded_at)
                    : 0;
            })
            ->sum();

        $attendanceSeconds = $attendance->check_in_at
            ? $attendance->check_in_at->diffInSeconds($attendance->check_out_at ?? now())
            : 0;

        $response = [
            'employeeId' => $employee->id,
            'employeeName' => $employee->name,
            'attendanceId' => $attendance?->id,
            'totalTrackedTime' => $this->formatSecondsAsClock($totalTrackedSeconds),
            'totalAttendanceTime' => $this->formatSecondsAsClock($attendanceSeconds),
            'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
            'totalKM' => $timeline['totalKM'],
            'gpsDistanceKm' => $timeline['totalKM'],
            'directionsDistanceKm' => null,
            'polylinePoints' => $timeline['polylinePoints'],
            'polylineSegments' => $timeline['polylineSegments'],
            'directionsSegments' => $timeline['directionsSegments'],
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

        $attendance = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->latest('check_in_at')
            ->first();

        $trackings = LocationTracking::query()
            ->with('attendance')
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($timelineStart, $timelineEnd) {
                $query->whereBetween('recorded_at', [$timelineStart, $timelineEnd])
                    ->orWhere(function ($query) use ($timelineStart, $timelineEnd) {
                        $query->whereNull('recorded_at')
                            ->whereBetween('created_at', [$timelineStart, $timelineEnd]);
                    });
            })
            ->orderByRaw('COALESCE(recorded_at, created_at) ASC')
            ->orderBy('id')
            ->get();

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
            'summary' => [
                'points_count' => $items->count(),
                'raw_points_count' => $trackings->count(),
                'total_tracked_seconds' => 0,
                'total_attendance_minutes' => $attendance?->worked_minutes ?? null,
            ],
            'trackings' => $items,
            'polyline_points' => $timeline['polylinePoints'],
            'polyline_segments' => $timeline['polylineSegments'],
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
        return max(60, (int) $this->settingValue('online_threshold_seconds', 1800));
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
                    'isWifiOn' => false,
                    'latitude' => (float) $tracking->latitude,
                    'longitude' => (float) $tracking->longitude,
                    'address' => null,
                    'signalStrength' => null,
                    'trackingType' => $tracking->type,
                    'segmentBreakBefore' => $previousTracking ? $this->shouldBreakTimelineSegment($previousTracking, $tracking) : false,
                    'startTime' => $tracking->recorded_at?->format('h:i A'),
                    'endTime' => $nextTracking?->recorded_at?->format('h:i A') ?? $tracking->recorded_at?->format('h:i A'),
                    'elapseTime' => $nextTracking && $tracking->recorded_at
                        ? $this->formatSecondsAsClock($tracking->recorded_at->diffInSeconds($nextTracking->recorded_at))
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
                'recorded_at' => ($tracking->recorded_at ?? $tracking->created_at)?->toDateTimeString(),
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

    private function isUnrealisticTimelineJump(LocationTracking $previous, LocationTracking $current, float $distanceKm): bool
    {
        if (! $previous->recorded_at || ! $current->recorded_at) {
            return false;
        }

        $seconds = max(1, $previous->recorded_at->diffInSeconds($current->recorded_at));
        $speedKmh = ($distanceKm / $seconds) * 3600;

        return $speedKmh > 120;
    }

    private function shouldBreakTimelineSegment(LocationTracking $previous, LocationTracking $current): bool
    {
        if ($previous->attendance_id !== $current->attendance_id) {
            return true;
        }

        if (! $previous->recorded_at || ! $current->recorded_at) {
            return false;
        }

        $distanceKm = $this->distanceInKm(
            (float) $previous->latitude,
            (float) $previous->longitude,
            (float) $current->latitude,
            (float) $current->longitude
        );
        $seconds = max(1, $previous->recorded_at->diffInSeconds($current->recorded_at));
        $speedKmh = ($distanceKm / $seconds) * 3600;

        return $seconds > (int) $this->settingValue('gps_max_inactive_gap_seconds', 600)
            || $distanceKm > 2
            || $speedKmh > ((float) $this->settingValue('gps_max_speed_mps', 25) * 3.6);
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

        if ($tracking->type === 'still') {
            return 'still';
        }

        $activity = strtolower((string) $tracking->activity);

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
            'minimum_distance_meters' => (float) $this->settingValue('gps_min_distance_metres', 5),
            'max_accuracy_meters' => (float) $this->settingValue('gps_max_accuracy_metres', 8),
            'simplify_after_points' => (int) $this->settingValue('timeline_simplify_after_points', 1000),
            'simplification_tolerance_meters' => (float) $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'douglas_peucker_tolerance_meters' => (float) $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'bearing_drift_distance_meters' => (float) $this->settingValue('gps_bearing_min_segment_distance_metres', $this->settingValue('gps_bearing_min_distance_metres', 10)),
            'bearing_change_degrees' => (float) $this->settingValue('timeline_bearing_change_degrees', 60),
            'max_bearing_change_degrees' => (float) $this->settingValue('gps_max_bearing_change_degrees', 45),
            'max_computed_speed_kmh' => (float) $this->settingValue('gps_max_speed_mps', 25) * 3.6,
        ];
    }

    private function timelineDateBounds(string $date): array
    {
        $timezone = config('app.timezone', 'UTC');
        $start = \Illuminate\Support\Carbon::parse($date, $timezone)->startOfDay();

        return [$start, $start->copy()->endOfDay()];
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
}
