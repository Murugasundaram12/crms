<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\EmployeeDevice;
use App\Models\LocationTracking;
use App\Models\User;
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
            ->where('employee_id', $employee->id)
            ->whereBetween('created_at', [$timelineStart, $timelineEnd])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if (! $attendance && $trackings->isEmpty()) {
            return response()->json([
                'employeeName' => $employee->name,
                'employeeId' => $employee->id,
                'totalTrackedTime' => '00:00:00',
                'totalAttendanceTime' => '00:00:00',
                'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
                'totalKM' => 0,
                'timeLineItems' => [],
            ]);
        }

        $timeLineItems = $this->timelineModuleItems($trackings);
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

        return response()->json([
            'employeeId' => $employee->id,
            'employeeName' => $employee->name,
            'attendanceId' => $attendance?->id,
            'totalTrackedTime' => $this->formatSecondsAsClock($totalTrackedSeconds),
            'totalAttendanceTime' => $this->formatSecondsAsClock($attendanceSeconds),
            'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
            'totalKM' => round((float) $timeLineItems->sum('distance'), 2),
            'polylinePoints' => $this->polylinePointsFromItems($timeLineItems),
            'timeLineItems' => $timeLineItems,
        ]);
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
            ->where('employee_id', $employee->id)
            ->whereBetween('created_at', [$timelineStart, $timelineEnd])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $filteredTrackings = collect($this->filterTimelineTrackings($trackings));

        $items = $filteredTrackings
            ->map(function (LocationTracking $tracking, int $index) use ($filteredTrackings) {
                $nextTracking = $filteredTrackings->get($index + 1);

                return [
                    'id' => $tracking->id,
                    'attendance_id' => $tracking->attendance_id,
                    'latitude' => (float) $tracking->latitude,
                    'longitude' => (float) $tracking->longitude,
                    'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
                    'speed' => $tracking->speed !== null ? (float) $tracking->speed : null,
                    'bearing' => $tracking->bearing !== null ? (float) $tracking->bearing : null,
                    'activity' => $tracking->activity,
                    'type' => $this->trackingTypeLabel($tracking),
                    'tracking_type' => $tracking->type,
                    'is_gps_on' => (bool) $tracking->is_gps_on,
                    'is_mock_location' => (bool) $tracking->is_mock_location,
                    'battery_percentage' => $tracking->battery_percentage,
                    'start_time' => $tracking->recorded_at?->format('h:i A'),
                    'end_time' => $nextTracking?->recorded_at?->format('h:i A') ?? $tracking->recorded_at?->format('h:i A'),
                    'elapsed_seconds' => $nextTracking && $tracking->recorded_at
                        ? $tracking->recorded_at->diffInSeconds($nextTracking->recorded_at)
                        : 0,
                    'recorded_at' => $tracking->recorded_at?->toISOString(),
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
                'total_tracked_seconds' => $items->sum('elapsed_seconds'),
                'total_attendance_minutes' => $attendance?->worked_minutes ?? null,
            ],
            'trackings' => $items,
            'polyline_points' => $this->polylinePointsFromItems($items, 'latitude', 'longitude'),
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
                $distance = $nextTracking && ! $this->shouldBreakTimelineSegment($tracking, $nextTracking)
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
                    'distance' => $type === 'vehicle' ? round($distance, 2) : 0,
                ];
            })
            ->values();
    }

    private function filterTimelineTrackings($trackings): array
    {
        return app(TimelineGpsProcessor::class)->filter($trackings, $this->timelineGpsOptions());
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

        return $seconds > 3600 || ($distanceKm > 5 && $speedKmh > 100);
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

        $activity = strtolower((string) $tracking->activity);

        if (in_array($activity, ['activitytype.still', 'still'], true)) {
            return $this->isStationaryTimelinePoint($tracking, $previous, $next) ? 'still' : 'vehicle';
        }

        return match (true) {
            in_array($activity, ['activitytype.walking', 'walking', 'walk'], true) => 'walk',
            default => 'vehicle',
        };
    }

    private function isStationaryTimelinePoint(LocationTracking $tracking, ?LocationTracking $previous, ?LocationTracking $next): bool
    {
        $speed = $tracking->speed !== null ? (float) $tracking->speed : null;
        if ($speed !== null && $speed > 0.5) {
            return false;
        }

        $nearbyPoints = collect([$previous, $next])->filter();
        if ($nearbyPoints->isEmpty()) {
            return false;
        }

        $nearbyStationaryPoints = $nearbyPoints->filter(function (LocationTracking $nearby) use ($tracking) {
            $nearbySpeed = $nearby->speed !== null ? (float) $nearby->speed : null;
            if ($nearbySpeed !== null && $nearbySpeed > 0.8) {
                return false;
            }

            $distanceMeters = $this->distanceInKm(
                (float) $tracking->latitude,
                (float) $tracking->longitude,
                (float) $nearby->latitude,
                (float) $nearby->longitude
            ) * 1000;

            return $distanceMeters <= 30;
        });

        return $nearbyStationaryPoints->isNotEmpty();
    }

    private function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return app(TimelineGpsProcessor::class)->distanceInKm($lat1, $lon1, $lat2, $lon2);
    }

    private function timelineGpsOptions(): array
    {
        return [
            'minimum_distance_meters' => (float) $this->settingValue('timeline_minimum_distance_meters', 10),
            'max_accuracy_meters' => (float) $this->settingValue('timeline_max_accuracy_meters', 20),
            'simplify_after_points' => (int) $this->settingValue('timeline_simplify_after_points', 1000),
            'simplification_tolerance_meters' => (float) $this->settingValue('timeline_simplification_tolerance_meters', 8),
            'bearing_drift_distance_meters' => (float) $this->settingValue('timeline_bearing_drift_distance_meters', 10),
            'bearing_change_degrees' => (float) $this->settingValue('timeline_bearing_change_degrees', 60),
            'max_computed_speed_kmh' => (float) $this->settingValue('timeline_max_computed_speed_kmh', 80),
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
        $points = [];
        $previous = null;

        foreach ($items as $item) {
            if (! isset($item[$latitudeKey], $item[$longitudeKey])) {
                continue;
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

        return collect($points);
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
        $setting = AppSetting::query()->where('key', $key)->first();

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
