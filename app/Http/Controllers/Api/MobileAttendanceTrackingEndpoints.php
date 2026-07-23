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
use App\Services\TimelineGpsProcessor;
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
            'bearing' => ['nullable', 'numeric', 'min:0', 'max:360'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_wifi_on' => ['nullable', 'boolean'],
            'isWifiOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryLevel' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery' => ['nullable', 'integer', 'min:0', 'max:100'],
            'signal_strength' => ['nullable', 'string', 'max:100'],
            'signalStrength' => ['nullable', 'string', 'max:100'],
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
        $gpsValidation = null;

        if (! blank($validated['latitude'] ?? null) && ! blank($validated['longitude'] ?? null)) {
            $trackingPayload = $this->normalizeOptionalTrackingPayload($validated, 'checked_in');
            $trackingPayload['recorded_at'] = $attendance->check_in_at;
            $gpsValidation = app(\App\Services\GpsTrackingValidationService::class)->validate($trackingPayload);

            if ($gpsValidation['accepted']) {
                DB::transaction(function () use ($user, $attendance, $trackingPayload, &$tracking) {
                    $this->upsertDeviceStatus($user->id, $trackingPayload);
                    $tracking = $this->createTrackingPoint($attendance, $trackingPayload, 'checked_in');
                });
            }
        }

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $this->attendancePayload($attendance),
            'saved' => (bool) $tracking,
            'reason' => $tracking ? null : ($gpsValidation['reason'] ?? null),
            'gps_validation' => $gpsValidation,
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
            'bearing' => ['nullable', 'numeric', 'min:0', 'max:360'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_wifi_on' => ['nullable', 'boolean'],
            'isWifiOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryLevel' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery' => ['nullable', 'integer', 'min:0', 'max:100'],
            'signal_strength' => ['nullable', 'string', 'max:100'],
            'signalStrength' => ['nullable', 'string', 'max:100'],
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
        $gpsValidation = null;

        DB::transaction(function () use ($user, $openAttendance, $checkoutTime, $notes, $validated, &$tracking, &$gpsValidation) {
            $openAttendance->update([
                'check_out_at' => $checkoutTime,
                'worked_minutes' => $openAttendance->check_in_at->diffInMinutes($checkoutTime),
                'notes' => $notes !== '' ? $notes : null,
            ]);

            if (! blank($validated['latitude'] ?? null) && ! blank($validated['longitude'] ?? null)) {
                $trackingPayload = $this->normalizeOptionalTrackingPayload($validated, 'checked_out');
                $trackingPayload['recorded_at'] = $checkoutTime;
                $previousTrackings = $this->latestValidTrackingPoints($user->id, $trackingPayload['device_id'] ?? 'default', 2);
                $gpsValidation = app(\App\Services\GpsTrackingValidationService::class)
                    ->validate($trackingPayload, $previousTrackings->get(0), $previousTrackings->get(1));

                if ($gpsValidation['accepted']) {
                    $this->upsertDeviceStatus($user->id, $trackingPayload);
                    $tracking = $this->createTrackingPoint($openAttendance, $trackingPayload, 'checked_out');
                }
            }
        });

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $this->attendancePayload($openAttendance->fresh()),
            'saved' => (bool) $tracking,
            'reason' => $tracking ? null : ($gpsValidation['reason'] ?? null),
            'gps_validation' => $gpsValidation,
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

    public function checkDevice(Request $request)
    {
        $validated = $this->validateDeviceRegistrationPayload($request);
        $user = $request->user();

        $sameUserSameDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($sameUserSameDevice) {
            return response()->json([
                'message' => 'Device verified successfully.',
                'status' => 'verified',
                'can_register' => false,
                'device' => $this->devicePayload($sameUserSameDevice),
            ]);
        }

        $otherUserDevice = EmployeeDevice::query()
            ->where('device_id', $validated['device_id'])
            ->where('employee_id', '!=', $user->id)
            ->first();

        if ($otherUserDevice) {
            return response()->json([
                'message' => 'Already registered with other user. Please contact admin.',
                'status' => 'blocked',
                'can_register' => false,
            ], 409);
        }

        $sameUserOtherDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', '!=', $validated['device_id'])
            ->first();

        if ($sameUserOtherDevice) {
            return response()->json([
                'message' => 'Already registered with other device. Please contact admin.',
                'status' => 'blocked',
                'can_register' => false,
                'device' => $this->devicePayload($sameUserOtherDevice),
            ], 409);
        }

        return response()->json([
            'message' => 'Device is not registered. Please register this device.',
            'status' => 'new',
            'can_register' => true,
        ]);
    }

    public function registerDevice(Request $request)
    {
        $validated = $this->validateDeviceRegistrationPayload($request);
        $user = $request->user();
        $tokenDeviceId = $this->mobileTokenDeviceId($request);

        $existingDevice = EmployeeDevice::query()
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($existingDevice && (int) $existingDevice->employee_id !== (int) $user->id) {
            return response()->json([
                'message' => 'This device is already registered. Please contact admin.',
            ], 409);
        }

        $sameUserSameDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->first();

        if ($sameUserSameDevice) {
            $sameUserSameDevice->update(collect($this->deviceValuesWithLatestTrackingFallback($user->id, $validated['device_id'], [
                'device_name' => $validated['device_name'] ?? null,
                'device_type' => $validated['device_type'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'board' => $validated['board'] ?? null,
                'sdk_version' => $validated['sdk_version'] ?? null,
                'model' => $validated['model'] ?? null,
                'battery_percentage' => $validated['battery_percentage'] ?? null,
                'last_seen_at' => now(),
            ]))->reject(fn ($value) => $value === null)->all());
            $this->rebindCurrentMobileTokenDevice($request, $validated['device_id']);

            return response()->json([
                'message' => 'Device verified successfully.',
                'status' => 'verified',
                'can_register' => false,
                'device' => $this->devicePayload($sameUserSameDevice->refresh()),
            ]);
        }

        $sameUserOtherDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', '!=', $validated['device_id'])
            ->first();

        if ($sameUserOtherDevice) {
            if ($this->isLegacyDeviceId($tokenDeviceId) && $sameUserOtherDevice->device_id === $tokenDeviceId) {
                $sameUserOtherDevice->update($this->deviceValuesWithLatestTrackingFallback($user->id, $validated['device_id'], [
                    'device_id' => $validated['device_id'],
                    'device_name' => $validated['device_name'] ?? $sameUserOtherDevice->device_name,
                    'device_type' => $validated['device_type'] ?? $sameUserOtherDevice->device_type,
                    'brand' => $validated['brand'] ?? $sameUserOtherDevice->brand,
                    'board' => $validated['board'] ?? $sameUserOtherDevice->board,
                    'sdk_version' => $validated['sdk_version'] ?? $sameUserOtherDevice->sdk_version,
                    'model' => $validated['model'] ?? $sameUserOtherDevice->model,
                    'battery_percentage' => $validated['battery_percentage'] ?? $sameUserOtherDevice->battery_percentage,
                    'last_seen_at' => now(),
                ]));
                $this->rebindCurrentMobileTokenDevice($request, $validated['device_id']);

                return response()->json([
                    'message' => 'Device registered successfully.',
                    'status' => 'verified',
                    'can_register' => false,
                    'device' => $this->devicePayload($sameUserOtherDevice->refresh()),
                ]);
            }

            return response()->json([
                'message' => 'Already registered with other device. Please contact admin.',
                'status' => 'blocked',
                'can_register' => false,
                'device' => $this->devicePayload($sameUserOtherDevice),
            ], 409);
        }

        if ($tokenDeviceId && $tokenDeviceId !== $validated['device_id'] && ! $this->isLegacyDeviceId($tokenDeviceId)) {
            $this->assertMobileTokenDeviceMatches($request, $validated['device_id']);
        }

        $device = EmployeeDevice::query()->create($this->availableEmployeeDeviceAttributes(
            $this->deviceValuesWithLatestTrackingFallback($user->id, $validated['device_id'], [
            'employee_id' => $user->id,
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'board' => $validated['board'] ?? null,
            'sdk_version' => $validated['sdk_version'] ?? null,
            'model' => $validated['model'] ?? null,
            'battery_percentage' => $validated['battery_percentage'] ?? null,
            'last_seen_at' => now(),
            ])
        ));

        $this->rebindCurrentMobileTokenDevice($request, $validated['device_id']);

        return response()->json([
            'message' => 'Device registered successfully.',
            'device' => $this->devicePayload($device),
        ], 201);
    }

    public function updateMessagingToken(Request $request)
    {
        $request->merge([
            'device_id' => $this->normalizeDeviceIdFromRequest($request) ?? $this->mobileTokenDeviceId($request),
        ]);

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'min:2', 'max:255'],
            'deviceId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUid' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'unique_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'uniqueId' => ['nullable', 'string', 'min:2', 'max:255'],
            'android_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'androidId' => ['nullable', 'string', 'min:2', 'max:255'],
            'messaging_token' => ['required_without:token', 'string', 'max:5000'],
            'token' => ['required_without:messaging_token', 'string', 'max:5000'],
        ]);
        $deviceId = $validated['device_id'];
        $this->assertMobileTokenDeviceMatches($request, $deviceId);

        $device = EmployeeDevice::query()
            ->where('employee_id', $request->user()->id)
            ->where('device_id', $deviceId)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not registered. Please contact admin.',
            ], 404);
        }

        $device->update($this->availableEmployeeDeviceAttributes([
            'messaging_token' => $validated['messaging_token'] ?? $validated['token'],
            'last_seen_at' => now(),
        ]));

        return response()->json([
            'message' => 'Messaging token updated successfully.',
            'device' => $this->devicePayload($device->refresh()),
        ]);
    }

    public function updateDeviceStatus(Request $request)
    {
        $validated = $this->validateDeviceStatusPayload($request);
        $user = $request->user();
        $this->assertMobileTokenDeviceMatches($request, $validated['device_id']);

        $device = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', $validated['device_id'])
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not registered. Please contact admin.',
            ], 404);
        }

        $device->update($this->availableEmployeeDeviceAttributes(collect([
            'device_name' => $validated['device_name'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'board' => $validated['board'] ?? null,
            'sdk_version' => $validated['sdk_version'] ?? null,
            'model' => $validated['model'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'bearing' => $validated['bearing'] ?? null,
            'activity' => $validated['activity'] ?? null,
            'is_gps_on' => $validated['is_gps_on'],
            'is_wifi_on' => $validated['is_wifi_on'],
            'is_mock_location' => $validated['is_mock_location'],
            'battery_percentage' => $validated['battery_percentage'] ?? null,
            'signal_strength' => $validated['signal_strength'] ?? null,
            'last_seen_at' => isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : now(),
        ])->reject(fn ($value) => $value === null)->all()));

        return response()->json([
            'message' => 'Device status updated successfully.',
            'device' => $this->devicePayload($device->refresh()),
        ]);
    }

    private function validateDeviceRegistrationPayload(Request $request): array
    {
        $request->merge([
            'device_id' => $this->normalizeDeviceIdFromRequest($request) ?? $this->mobileTokenDeviceId($request),
        ]);

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'min:2', 'max:255'],
            'deviceId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUid' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'unique_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'uniqueId' => ['nullable', 'string', 'min:2', 'max:255'],
            'android_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'androidId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_name' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceName' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'deviceType' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'board' => ['nullable', 'string', 'max:100'],
            'sdk_version' => ['nullable', 'string', 'max:100'],
            'sdkVersion' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryLevel' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        return [
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'] ?? $validated['deviceName'] ?? null,
            'device_type' => $validated['device_type'] ?? $validated['deviceType'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'board' => $validated['board'] ?? null,
            'sdk_version' => $validated['sdk_version'] ?? $validated['sdkVersion'] ?? null,
            'model' => $validated['model'] ?? null,
            'battery_percentage' => $this->batteryPercentageFromPayload($validated),
        ];
    }

    private function validateDeviceStatusPayload(Request $request): array
    {
        $request->merge([
            'device_id' => $this->normalizeDeviceIdFromRequest($request) ?? $this->mobileTokenDeviceId($request),
        ]);

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'min:2', 'max:255'],
            'deviceId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUid' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'unique_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'uniqueId' => ['nullable', 'string', 'min:2', 'max:255'],
            'android_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'androidId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'deviceName' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'deviceType' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'board' => ['nullable', 'string', 'max:100'],
            'sdk_version' => ['nullable', 'string', 'max:100'],
            'sdkVersion' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'bearing' => ['nullable', 'numeric', 'min:0', 'max:360'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_wifi_on' => ['nullable', 'boolean'],
            'isWifiOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryLevel' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery' => ['nullable', 'integer', 'min:0', 'max:100'],
            'signal_strength' => ['nullable', 'string', 'max:100'],
            'signalStrength' => ['nullable', 'string', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        return [
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'] ?? $validated['deviceName'] ?? null,
            'device_type' => $validated['device_type'] ?? $validated['deviceType'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'board' => $validated['board'] ?? null,
            'sdk_version' => $validated['sdk_version'] ?? $validated['sdkVersion'] ?? null,
            'model' => $validated['model'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'bearing' => $validated['bearing'] ?? null,
            'activity' => $validated['activity'] ?? null,
            'is_gps_on' => (bool) ($validated['is_gps_on'] ?? $validated['isGpsOn'] ?? true),
            'is_wifi_on' => (bool) ($validated['is_wifi_on'] ?? $validated['isWifiOn'] ?? false),
            'is_mock_location' => (bool) ($validated['is_mock_location'] ?? $validated['isMock'] ?? false),
            'battery_percentage' => $this->batteryPercentageFromPayload($validated),
            'signal_strength' => $validated['signal_strength'] ?? $validated['signalStrength'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? null,
        ];
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

        if ($this->isOfflinePayloadTooOld($validated)) {
            return response()->json([
                'success' => true,
                'saved' => false,
                'reason' => 'offline_record_too_old',
                'message' => 'Location received but ignored because offline record is older than the allowed sync age.',
            ]);
        }

        $attendance = $this->attendanceForTrackingPayload($user->id, $validated);

        if (! $attendance) {
            return response()->json([
                'success' => false,
                'saved' => false,
                'reason' => 'no_active_attendance',
                'message' => 'No attendance session found for this tracking time.',
            ], 409);
        }

        try {
            [$tracking, $inserted, $gpsValidation] = $this->storeTrackingUpdate($user, $attendance, $validated);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Mobile tracking location update failed.', [
                'employee_id' => $user->id,
                'attendance_id' => $attendance->id,
                'device_id' => $validated['device_id'] ?? null,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'saved' => false,
                'reason' => 'server_exception',
                'message' => 'Location update failed on server.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'saved' => $inserted,
            'message' => $inserted ? 'Location updated successfully.' : $this->trackingIgnoredMessage($gpsValidation['reason'] ?? null),
            'reason' => $gpsValidation['reason'] ?? null,
            'gps_validation' => $gpsValidation,
            'inserted' => $inserted,
            'tracking' => $tracking ? $this->trackingPayload($tracking) : null,
        ], $inserted ? 201 : 200);
    }

    public function syncOfflineLocations(Request $request)
    {
        if (! $this->settingValue('tracking_enabled', true)) {
            return response()->json([
                'message' => 'Location tracking is disabled by mobile app settings.',
            ], 403);
        }

        if (! $this->settingValue('offline_tracking_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Offline tracking sync is disabled by mobile app settings.',
            ], 403);
        }

        $bulkUploadLimit = min(500, max(1, (int) $this->settingValue('bulk_upload_limit', 100)));
        $validatedBatch = $request->validate([
            'locations' => ['required', 'array', 'min:1', 'max:' . $bulkUploadLimit],
            'locations.*' => ['required', 'array'],
        ]);

        $user = $request->user();
        $results = [];
        $savedCount = 0;
        $ignoredCount = 0;
        $failedCount = 0;

        foreach (array_values($validatedBatch['locations']) as $index => $locationPayload) {
            $pointRequest = $this->trackingPointRequest($request, [
                ...$request->except('locations'),
                ...$locationPayload,
                'is_offline' => $locationPayload['is_offline'] ?? $locationPayload['isOffline'] ?? $locationPayload['offline'] ?? true,
            ]);

            try {
                $validated = $this->validateTrackingPayload($pointRequest, 'travelling');

                if ($this->isOfflinePayloadTooOld($validated)) {
                    $ignoredCount++;
                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'saved' => false,
                        'reason' => 'offline_record_too_old',
                        'message' => 'Location received but ignored because offline record is older than the allowed sync age.',
                    ];
                    continue;
                }

                $attendance = $this->attendanceForTrackingPayload($user->id, $validated);

                if (! $attendance) {
                    $failedCount++;
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'saved' => false,
                        'reason' => 'no_active_attendance',
                        'message' => 'No attendance session found for this tracking time.',
                    ];
                    continue;
                }

                [$tracking, $inserted, $gpsValidation] = $this->storeTrackingUpdate($user, $attendance, $validated);
                $inserted ? $savedCount++ : $ignoredCount++;

                $results[] = [
                    'index' => $index,
                    'success' => true,
                    'saved' => $inserted,
                    'reason' => $gpsValidation['reason'] ?? null,
                    'message' => $inserted ? 'Location updated successfully.' : $this->trackingIgnoredMessage($gpsValidation['reason'] ?? null),
                    'tracking' => $tracking ? $this->trackingPayload($tracking) : null,
                ];
            } catch (ValidationException $exception) {
                $failedCount++;
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'saved' => false,
                    'reason' => 'validation_failed',
                    'message' => collect($exception->errors())->flatten()->first() ?? 'Location validation failed.',
                    'errors' => $exception->errors(),
                ];
            } catch (\Throwable $exception) {
                $failedCount++;
                \Illuminate\Support\Facades\Log::error('Mobile offline tracking sync point failed.', [
                    'employee_id' => $user->id,
                    'index' => $index,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'saved' => false,
                    'reason' => 'server_exception',
                    'message' => 'Location update failed on server.',
                ];
            }
        }

        return response()->json([
            'success' => $failedCount === 0,
            'message' => 'Offline tracking sync processed.',
            'saved_count' => $savedCount,
            'ignored_count' => $ignoredCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ], $failedCount > 0 ? 207 : 200);
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
        $settings = $this->trackingSettingsPayload();

        return response()->json([
            ...$settings,
            'tracking_interval_seconds' => $settings['trackingIntervalSeconds'],
            'location_update_interval' => $settings['locationUpdateInterval'],
            'location_update_interval_type' => $settings['locationUpdateIntervalType'],
            'minimum_accuracy' => $settings['minimumAccuracy'],
            'minimum_distance_meters' => $settings['minimumDistanceMeters'],
            'max_accuracy_meters' => $settings['minimumAccuracy'],
            'maximum_speed_kmph' => $settings['maximumSpeedKmph'],
            'timeline_minimum_distance_meters' => $this->settingValue('gps_min_distance_metres', 30),
            'timeline_max_accuracy_meters' => $this->settingValue('gps_max_accuracy_metres', 50),
            'timeline_simplify_after_points' => $this->settingValue('timeline_simplify_after_points', 1000),
            'timeline_simplification_tolerance_meters' => $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'timeline_bearing_drift_distance_meters' => $this->settingValue('gps_bearing_min_segment_distance_metres', $this->settingValue('gps_bearing_min_distance_metres', 10)),
            'timeline_bearing_change_degrees' => $this->settingValue('timeline_bearing_change_degrees', 60),
            'timeline_max_bearing_change_degrees' => $this->settingValue('gps_max_bearing_change_degrees', 45),
            'timeline_max_computed_speed_kmh' => $this->settingValue('timeline_max_computed_speed_kmh', 90),
            'gps_max_accuracy_metres' => $this->settingValue('gps_max_accuracy_metres', 50),
            'gps_min_distance_metres' => $this->settingValue('gps_min_distance_metres', 30),
            'gps_max_speed_mps' => $settings['maximumSpeedKmph'] / 3.6,
            'gps_max_bearing_change_degrees' => $this->settingValue('gps_max_bearing_change_degrees', 45),
            'gps_bearing_min_distance_metres' => $this->settingValue('gps_bearing_min_segment_distance_metres', $this->settingValue('gps_bearing_min_distance_metres', 10)),
            'gps_douglas_peucker_tolerance_metres' => $this->settingValue('gps_douglas_peucker_tolerance_metres', 3),
            'gps_max_inactive_gap_seconds' => (int) round($settings['largeGapMinutes'] * 60),
            'large_gap_minutes' => $settings['largeGapMinutes'],
            'large_gap_distance_meters' => $settings['largeGapDistanceMeters'],
            'mock_location_allowed' => $settings['mockLocationAllowed'],
            'history_retention_days' => $this->settingValue('history_retention_days', 90),
            'offline_tracking_enabled' => $settings['offlineTrackingEnabled'],
            'offline_check_time' => $settings['offlineCheckTime'],
            'offline_check_time_type' => $settings['offlineCheckTimeType'],
            'online_threshold_seconds' => $settings['onlineThresholdSeconds'],
            'bulk_upload_limit' => $settings['bulkUploadLimit'],
            'allow_sync_after_checkout' => $settings['allowSyncAfterCheckout'],
            'low_signal_threshold' => $settings['lowSignalThreshold'],
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
                $onlineStatus = $this->isDeviceOnline($device) ? 'online' : 'offline';

                return [
                    ...$this->devicePayload($device),
                    'employee' => $this->userPayload($device->employee),
                    'online_status' => $onlineStatus,
                    'status' => $onlineStatus,
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
        [$timelineStart, $timelineEnd] = $this->timelineDateBounds($date);

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
            ->where('employee_id', $employeeId)
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

        $moduleItems = $this->timelineModuleItems($trackings);

        $filteredTrackings = collect($this->filterTimelineTrackings($trackings));

        $items = $filteredTrackings
            ->map(function (LocationTracking $tracking, int $index) use ($filteredTrackings) {
                $nextTracking = $filteredTrackings->get($index + 1);

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
                'raw_points_count' => $trackings->count(),
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
            'polylinePoints' => $this->polylinePointsFromItems($moduleItems),
            'polylineSegments' => $this->polylineSegmentsFromItems($moduleItems),
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
                $isOnline = $device && $this->isDeviceOnline($device);

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

    protected function filterTimelineTrackings($trackings): array
    {
        return app(TimelineGpsProcessor::class)->filter($trackings, $this->timelineGpsOptions());
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

    protected function shouldBreakTimelineSegment(LocationTracking $previous, LocationTracking $current): bool
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

        $intervalSeconds = max(1, (int) $this->settingValue('tracking_interval_seconds', 30));
        $gapThresholdSeconds = max($intervalSeconds * 3, (int) $this->settingValue('gps_max_inactive_gap_seconds', 90));

        return $seconds >= $gapThresholdSeconds
            || $distanceKm > 2
            || $speedKmh > ((float) $this->settingValue('gps_max_speed_mps', 25) * 3.6);
    }

    protected function timelineModuleType(LocationTracking $tracking, ?LocationTracking $previous = null, ?LocationTracking $next = null): string
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

        return match (true) {
            in_array($activity, ['activitytype.walking', 'walking', 'walk'], true) => 'walk',
            default => 'vehicle',
        };
    }

    protected function isTimelineMovementType(?string $type): bool
    {
        return in_array($type, ['vehicle', 'walk'], true);
    }

    protected function isStationaryTimelinePoint(LocationTracking $tracking, ?LocationTracking $previous, ?LocationTracking $next): bool
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

    protected function distanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return app(TimelineGpsProcessor::class)->distanceInKm($lat1, $lon1, $lat2, $lon2);
    }

    protected function timelineGpsOptions(): array
    {
        return [
            'minimum_distance_meters' => (float) $this->settingValue('timeline_minimum_distance_meters', 30),
            'max_accuracy_meters' => (float) $this->settingValue('timeline_max_accuracy_meters', 50),
            'simplify_after_points' => (int) $this->settingValue('timeline_simplify_after_points', 1000),
            'simplification_tolerance_meters' => (float) $this->settingValue('timeline_simplification_tolerance_meters', 8),
            'bearing_drift_distance_meters' => (float) $this->settingValue('timeline_bearing_drift_distance_meters', 10),
            'bearing_change_degrees' => (float) $this->settingValue('timeline_bearing_change_degrees', 60),
            'max_bearing_change_degrees' => (float) $this->settingValue('timeline_max_bearing_change_degrees', 170),
            'max_computed_speed_kmh' => (float) $this->settingValue('timeline_max_computed_speed_kmh', 90),
            'tracking_interval_seconds' => (int) $this->settingValue('tracking_interval_seconds', 30),
            'gps_max_inactive_gap_seconds' => (int) $this->settingValue('gps_max_inactive_gap_seconds', 90),
        ];
    }

    protected function trackingIgnoredMessage(?string $reason): string
    {
        return match ($reason) {
            'distance_below_threshold' => 'Location received but ignored because movement was too small.',
            'duplicate_location' => 'Location received but ignored because it duplicates the previous point.',
            'invalid_timestamp' => 'Location received but ignored because recorded_at is not newer than the previous point.',
            'speed_exceeded' => 'Location received but ignored because movement speed was impossible.',
            'accuracy_exceeded' => 'Location received but ignored because GPS accuracy is above threshold.',
            'mock_location' => 'Location received but ignored because mock location is not allowed.',
            'invalid_coordinates' => 'Location received but ignored because coordinates are invalid.',
            'duplicate_retry' => 'Location already saved earlier; duplicate retry ignored.',
            'offline_record_too_old' => 'Location received but ignored because offline record is older than the allowed sync age.',
            default => 'Location received but ignored by GPS validation.',
        };
    }

    protected function timelineDateBounds(string $date): array
    {
        $timezone = config('app.timezone', 'UTC');
        $start = Carbon::parse($date, $timezone)->startOfDay();

        return [$start, $start->copy()->endOfDay()];
    }

    protected function polylinePointsFromItems($items, string $latitudeKey = 'latitude', string $longitudeKey = 'longitude')
    {
        return collect($this->polylineSegmentsFromItems($items, $latitudeKey, $longitudeKey))
            ->flatten(1)
            ->values();
    }

    protected function polylineSegmentsFromItems($items, string $latitudeKey = 'latitude', string $longitudeKey = 'longitude'): array
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
