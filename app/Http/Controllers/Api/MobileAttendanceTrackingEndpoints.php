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
        $canListAll = $this->canUseApiPermission($request->user(), 'attendance-list');

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['checked_in', 'checked_out'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Attendance::query()
            ->with('user')
            ->latest('attendance_date')
            ->latest('check_in_at');

        if (! $canListAll) {
            $query->where('user_id', $request->user()->id);
        } elseif (! blank($validated['user_id'] ?? null)) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! blank($validated['from_date'] ?? null)) {
            $query->whereDate('attendance_date', '>=', $request->date('from_date')->toDateString());
        }

        if (! blank($validated['to_date'] ?? null)) {
            $query->whereDate('attendance_date', '<=', $request->date('to_date')->toDateString());
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

    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
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

        $trackings = LocationTracking::query()
            ->where('employee_id', $employeeId)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get()
            ->map(fn(LocationTracking $tracking) => $this->trackingPayload($tracking));

        return response()->json([
            'employee' => $this->userPayload(User::query()->findOrFail($employeeId)),
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
            'trackings' => $trackings,
        ]);
    }
}

