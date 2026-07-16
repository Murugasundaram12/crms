<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocationTracking;
use App\Models\MainCategory;
use App\Models\MobileApiToken;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobileAttendanceTrackingEndpoints
{
    public function checkIn(Request $request)
    {
        if (! $this->settingValue('attendance_enabled', true) || ! $this->settingValue('check_in_enabled', true)) {
            return response()->json([
                'message' => 'Check-in is disabled by mobile app settings.',
            ], 403);
        }

        $maxAccuracyMeters = $this->settingValue('max_accuracy_meters', 50);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:' . $maxAccuracyMeters],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $today = now()->toDateString();

        $activeAttendance = $this->activeAttendance($user->id);

        if ($activeAttendance) {
            return response()->json([
                'message' => 'You are already checked in.',
                'attendance' => $this->attendancePayload($activeAttendance),
            ], 409);
        }

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->latest('check_in_at')
            ->first();

        if ($todayAttendance) {
            return response()->json([
                'message' => 'You have already checked in today.',
                'attendance' => $this->attendancePayload($todayAttendance),
            ], 409);
        }

        $attendance = Attendance::query()->create([
            'user_id' => $user->id,
            'attendance_date' => $today,
            'check_in_at' => now(),
            'status' => 'present',
            'notes' => $validated['notes'] ?? null,
        ]);

        $tracking = null;

        if (! blank($validated['latitude'] ?? null) && ! blank($validated['longitude'] ?? null)) {
            $trackingPayload = $this->normalizeOptionalTrackingPayload($validated, 'checked_in');
            $trackingPayload['recorded_at'] = $attendance->check_in_at;

            DB::transaction(function () use ($user, $attendance, $trackingPayload, &$tracking) {
                $this->upsertDeviceStatus($user->id, $trackingPayload);
                $tracking = $this->createTrackingPoint($attendance, $trackingPayload, 'checked_in');
            });
        }

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $this->attendancePayload($attendance),
            'tracking' => $tracking ? $this->trackingPayload($tracking) : null,
        ], 201);
    }

    public function checkOut(Request $request)
    {
        if (! $this->settingValue('attendance_enabled', true) || ! $this->settingValue('check_out_enabled', true)) {
            return response()->json([
                'message' => 'Check-out is disabled by mobile app settings.',
            ], 403);
        }

        $maxAccuracyMeters = $this->settingValue('max_accuracy_meters', 50);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:' . $maxAccuracyMeters],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        $openAttendance = $this->activeAttendance($user->id);

        if (! $openAttendance) {
            return response()->json([
                'message' => 'No active check-in found.',
            ], 404);
        }

        if ($blockResponse = $this->incompleteDueTasksBlockResponse($user, 'check-out')) {
            return $blockResponse;
        }

        $checkoutTime = now();
        $notes = trim(collect([$openAttendance->notes, $validated['notes'] ?? null])->filter()->implode("\n"));

        $tracking = null;

        DB::transaction(function () use ($user, $openAttendance, $checkoutTime, $notes, $validated, &$tracking) {
            $openAttendance->update([
                'check_out_at' => $checkoutTime,
                'worked_minutes' => $openAttendance->check_in_at->diffInMinutes($checkoutTime),
                'notes' => $notes !== '' ? $notes : null,
            ]);

            if (! blank($validated['latitude'] ?? null) && ! blank($validated['longitude'] ?? null)) {
                $trackingPayload = $this->normalizeOptionalTrackingPayload($validated, 'checked_out');
                $trackingPayload['recorded_at'] = $checkoutTime;
                $this->upsertDeviceStatus($user->id, $trackingPayload);
                $tracking = $this->createTrackingPoint($openAttendance, $trackingPayload, 'checked_out');
            }
        });

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $this->attendancePayload($openAttendance->fresh()),
            'tracking' => $tracking ? $this->trackingPayload($tracking) : null,
        ]);
    }

    public function attendances(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['checked_in', 'checked_out'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Attendance::query()
            ->with('user')
            ->latest('attendance_date')
            ->latest('check_in_at');

        if ($this->canViewAllAppData($request->user())) {
            if (! blank($validated['user_id'] ?? null)) {
                $query->where('user_id', (int) $validated['user_id']);
            }
        } else {
            $query->where('user_id', $request->user()->id);
        }

        $fromDate = $validated['from_date'] ?? $validated['from'] ?? null;
        $toDate = $validated['to_date'] ?? $validated['to'] ?? null;

        if (! blank($fromDate)) {
            $query->whereDate('attendance_date', '>=', Carbon::parse($fromDate)->toDateString());
        }

        if (! blank($toDate)) {
            $query->whereDate('attendance_date', '<=', Carbon::parse($toDate)->toDateString());
        }

        if (($validated['status'] ?? null) === 'checked_out') {
            $query->whereNotNull('check_out_at');
        }

        if (($validated['status'] ?? null) === 'checked_in') {
            $query->whereNull('check_out_at');
        }

        $attendances = $query->paginate((int) ($validated['per_page'] ?? 15));
        $attendances->setCollection($attendances->getCollection()->map(fn(Attendance $attendance) => [
            ...$this->attendancePayload($attendance),
            'user' => $this->userPayload($attendance->user),
        ]));

        return response()->json($attendances);
    }

    public function myAttendances(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['checked_in', 'checked_out'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Attendance::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->latest('attendance_date')
            ->latest('check_in_at');

        $fromDate = $validated['from_date'] ?? $validated['from'] ?? null;
        $toDate = $validated['to_date'] ?? $validated['to'] ?? null;

        if (! blank($fromDate)) {
            $query->whereDate('attendance_date', '>=', Carbon::parse($fromDate)->toDateString());
        }

        if (! blank($toDate)) {
            $query->whereDate('attendance_date', '<=', Carbon::parse($toDate)->toDateString());
        }

        if (($validated['status'] ?? null) === 'checked_out') {
            $query->whereNotNull('check_out_at');
        }

        if (($validated['status'] ?? null) === 'checked_in') {
            $query->whereNull('check_out_at');
        }

        $attendances = $query->paginate((int) ($validated['per_page'] ?? 15));
        $attendances->setCollection($attendances->getCollection()->map(fn(Attendance $attendance) => [
            ...$this->attendancePayload($attendance),
            'user' => $this->userPayload($attendance->user),
        ]));

        return response()->json($attendances);
    }

    public function attendanceStatus(Request $request)
    {
        $canViewAll = $this->canUseApiPermission($request->user(), 'attendance-list');

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'date' => ['nullable', 'date'],
        ]);

        $userId = $request->user()->id;

        if ($canViewAll && ! blank($validated['user_id'] ?? null)) {
            $userId = (int) $validated['user_id'];
        }

        $date = ! blank($validated['date'] ?? null)
            ? $request->date('date')->toDateString()
            : now()->toDateString();

        $activeAttendance = $this->activeAttendance($userId);

        $latestAttendance = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', $date)
            ->latest('check_in_at')
            ->first();

        if (! $latestAttendance && $activeAttendance) {
            $latestAttendance = $activeAttendance;
        }

        return response()->json([
            'user_id' => $userId,
            'date' => $date,
            'status' => $activeAttendance ? 'checked_in' : 'checked_out',
            'is_checked_in' => (bool) $activeAttendance,
            'can_check_in' => ! $activeAttendance,
            'can_check_out' => (bool) $activeAttendance,
            'active_attendance' => $activeAttendance ? $this->attendancePayload($activeAttendance) : null,
            'latest_attendance' => $latestAttendance ? $this->attendancePayload($latestAttendance) : null,
        ]);
    }

    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'min:2', 'max:255'],
            'device_name' => ['nullable', 'string', 'min:2', 'max:255'],
        ]);

        $device = EmployeeDevice::query()->updateOrCreate(
            [
                'employee_id' => $request->user()->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'device_name' => $validated['device_name'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully.',
            'device' => $this->devicePayload($device),
        ], 201);
    }

    public function updateLocation(Request $request)
    {
        if (! $this->settingValue('tracking_enabled', true)) {
            return response()->json([
                'message' => 'Location tracking is disabled by mobile app settings.',
            ], 403);
        }

        $validated = $this->validateTrackingPayload($request, 'travelling');
        $user = $request->user();

        $attendance = $this->activeAttendance($user->id);

        if (! $attendance) {
            return response()->json([
                'message' => 'No active attendance found. Tracking is allowed only after check-in and before check-out.',
            ], 409);
        }

        $tracking = DB::transaction(function () use ($user, $attendance, $validated) {
            $this->upsertDeviceStatus($user->id, $validated);

            return $this->createTrackingPoint($attendance, $validated, $validated['type'] ?? 'travelling');
        });

        return response()->json([
            'message' => 'Location updated successfully.',
            'tracking' => $this->trackingPayload($tracking),
        ], 201);
    }

    public function liveStatus(Request $request)
    {
        if (! $this->settingValue('tracking_enabled', true)) {
            return response()->json([
                'message' => 'Live status tracking is disabled by mobile app settings.',
            ], 403);
        }

        $validated = $this->validateTrackingPayload($request, 'travelling');
        $user = $request->user();

        if (! $this->activeAttendance($user->id)) {
            return response()->json([
                'message' => 'No active attendance found. Tracking is allowed only after check-in and before check-out.',
            ], 409);
        }

        $device = $this->upsertDeviceStatus($user->id, $validated);

        return response()->json([
            'message' => 'Live status updated successfully.',
            'device' => $this->devicePayload($device),
        ]);
    }

    public function trackingSettings()
    {
        return response()->json([
            'tracking_interval_seconds' => $this->settingValue('tracking_interval_seconds', 60),
            'minimum_distance_meters' => $this->settingValue('minimum_distance_meters', 25),
            'max_accuracy_meters' => $this->settingValue('max_accuracy_meters', 50),
            'mock_location_allowed' => $this->settingValue('mock_location_allowed', false),
            'history_retention_days' => $this->settingValue('history_retention_days', 90),
            'offline_tracking_enabled' => $this->settingValue('offline_tracking_enabled', true),
        ]);
    }

    public function adminLiveLocations(Request $request)
    {
        if (! $this->canViewEmployeeTracking($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $devices = EmployeeDevice::query()
            ->with('employee')
            ->where('employee_id', '!=', $request->user()->id)
            ->latest('last_seen_at')
            ->get()
            ->map(function (EmployeeDevice $device) {
                return [
                    ...$this->devicePayload($device),
                    'employee' => $this->userPayload($device->employee),
                    'online_status' => $device->last_seen_at && $device->last_seen_at->gt(now()->subSeconds(120)) ? 'online' : 'offline',
                ];
            });

        return response()->json(['data' => $devices]);
    }

    public function adminTimeline(Request $request, int $employeeId)
    {
        if (! $this->canViewEmployeeTracking($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $employeeId)
            ->whereDate('attendance_date', $date)
            ->latest('check_in_at')
            ->first();

        $employee = User::query()->findOrFail($employeeId);
        $device = EmployeeDevice::query()
            ->where('employee_id', $employeeId)
            ->latest('last_seen_at')
            ->first();

        $trackings = LocationTracking::query()
            ->when(
                $attendance,
                fn ($query) => $query->where('attendance_id', $attendance->id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('recorded_at')
            ->get();

        $moduleItems = $this->timelineModuleItems($trackings);

        $items = $trackings
            ->map(function (LocationTracking $tracking, int $index) use ($trackings) {
                $nextTracking = $trackings->get($index + 1);

                return [
                    ...$this->trackingPayload($tracking),
                    'tracking_type' => $tracking->type,
                    'type_label' => $this->trackingTypeLabel($tracking),
                    'start_time' => $tracking->recorded_at?->format('h:i A'),
                    'end_time' => $nextTracking?->recorded_at?->format('h:i A') ?? $tracking->recorded_at?->format('h:i A'),
                    'elapsed_seconds' => $nextTracking && $tracking->recorded_at
                        ? $tracking->recorded_at->diffInSeconds($nextTracking->recorded_at)
                        : 0,
                ];
            })
            ->values();

        $totalTrackedSeconds = $items->sum('elapsed_seconds');
        $attendanceSeconds = $attendance && $attendance->check_in_at
            ? $attendance->check_in_at->diffInSeconds($attendance->check_out_at ?? now())
            : 0;

        return response()->json([
            'employee' => $this->userPayload($employee),
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
            'summary' => [
                'points_count' => $items->count(),
                'total_tracked_seconds' => $totalTrackedSeconds,
                'total_attendance_minutes' => $attendance?->worked_minutes ?? null,
                'total_attendance_duration' => $attendance?->worked_minutes === null
                    ? null
                    : $this->formatWorkedDuration((int) $attendance->worked_minutes),
            ],
            'trackings' => $items,
            'employeeId' => $employee->id,
            'employeeName' => $employee->name,
            'attendanceId' => $attendance?->id,
            'totalTrackedTime' => $this->formatSecondsAsClock($totalTrackedSeconds),
            'totalAttendanceTime' => $this->formatSecondsAsClock($attendanceSeconds),
            'deviceInfo' => $device ? trim(collect([$device->device_name, $device->device_id])->filter()->implode(' ')) : null,
            'totalKM' => round((float) $moduleItems->sum('distance'), 2),
            'timeLineItems' => $moduleItems,
        ]);
    }

    public function adminTimelineModule(Request $request)
    {
        if (! $this->canViewEmployeeTracking($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        return $this->adminTimeline($request, (int) $validated['userId']);
    }

    public function adminCardView(Request $request)
    {
        if (! $this->canViewEmployeeTracking($request->user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $todayAttendances = Attendance::query()
            ->with('user')
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

        $cards = $todayAttendances
            ->map(function (Attendance $attendance) use ($devicesByUser) {
                $device = $devicesByUser->get($attendance->user_id);
                $isOnline = $device?->last_seen_at && $device->last_seen_at->gt(now()->subSeconds(120));

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
                    'attendance' => $this->attendancePayload($attendance),
                    'latest_location' => $device ? $this->devicePayload($device) : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $cards,
            'summary' => [
                'all' => $cards->count(),
                'on_duty' => $cards->where('isOnline', true)->where('attendanceOutAt', '')->count(),
                'inactive' => $cards->where('isOnline', false)->where('attendanceOutAt', '')->filter(fn(array $card) => filled($card['attendanceInAt']))->count(),
                'off_duty' => $cards->filter(fn(array $card) => filled($card['attendanceOutAt']) || blank($card['attendanceInAt']))->count(),
            ],
        ]);
    }

    protected function timelineModuleItems($trackings)
    {
        $filteredTrackings = $this->filterTimelineTrackings($trackings);

        return collect($filteredTrackings)
            ->map(function (LocationTracking $tracking, int $index) use ($filteredTrackings) {
                $nextTracking = $filteredTrackings[$index + 1] ?? null;
                $distance = $nextTracking
                    ? $this->distanceInKm(
                        (float) $tracking->latitude,
                        (float) $tracking->longitude,
                        (float) $nextTracking->latitude,
                        (float) $nextTracking->longitude
                    )
                    : 0;

                $type = $this->timelineModuleType($tracking);

                return [
                    'id' => $tracking->id,
                    'type' => $type,
                    'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : 0,
                    'activity' => $tracking->activity,
                    'batteryPercentage' => $tracking->battery_percentage,
                    'isGPSOn' => (bool) $tracking->is_gps_on,
                    'isWifiOn' => false,
                    'latitude' => (float) $tracking->latitude,
                    'longitude' => (float) $tracking->longitude,
                    'address' => null,
                    'signalStrength' => null,
                    'trackingType' => $tracking->type,
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

    protected function filterTimelineTrackings($trackings): array
    {
        if ($trackings->count() === 0) {
            return [];
        }

        $filtered = [];
        $minimumDistanceKm = max(0.01, ((float) $this->settingValue('minimum_distance_meters', 25)) / 1000);

        foreach ($trackings as $tracking) {
            if (in_array($tracking->type, ['checked_in', 'checked_out'], true)) {
                $filtered[] = $tracking;
                continue;
            }

            if (count($filtered) === 0) {
                $filtered[] = $tracking;
                continue;
            }

            $lastTracking = $filtered[count($filtered) - 1];
            $distance = $this->distanceInKm(
                (float) $lastTracking->latitude,
                (float) $lastTracking->longitude,
                (float) $tracking->latitude,
                (float) $tracking->longitude
            );

            if ($distance < $minimumDistanceKm) {
                continue;
            }

            if ($this->isUnrealisticTimelineJump($lastTracking, $tracking, $distance)) {
                continue;
            }

            $filtered[] = $tracking;
        }

        return array_slice($filtered, 0, 500);
    }

    protected function isUnrealisticTimelineJump(LocationTracking $previous, LocationTracking $current, float $distanceKm): bool
    {
        if (! $previous->recorded_at || ! $current->recorded_at) {
            return false;
        }

        $seconds = max(1, $previous->recorded_at->diffInSeconds($current->recorded_at));
        $speedKmh = ($distanceKm / $seconds) * 3600;

        return $speedKmh > 120;
    }

    protected function timelineModuleType(LocationTracking $tracking): string
    {
        if ($tracking->type === 'checked_in') {
            return 'checkIn';
        }

        if ($tracking->type === 'checked_out') {
            return 'checkOut';
        }

        $activity = strtolower((string) $tracking->activity);

        return match (true) {
            in_array($activity, ['activitytype.still', 'still'], true) => 'still',
            in_array($activity, ['activitytype.walking', 'walking', 'walk'], true) => 'walk',
            default => 'vehicle',
        };
    }

    protected function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $latDistance = deg2rad($lat2 - $lat1);
        $lonDistance = deg2rad($lon2 - $lon1);
        $a = sin($latDistance / 2) * sin($latDistance / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDistance / 2) * sin($lonDistance / 2);

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    protected function formatSecondsAsClock(int|float $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }
}
