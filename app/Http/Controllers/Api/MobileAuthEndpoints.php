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
use App\Services\SingleLoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobileAuthEndpoints
{
    public function login(Request $request)
    {
        $request->merge([
            'device_id' => $this->normalizeDeviceIdFromRequest($request),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUid' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_uuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'deviceUuid' => ['nullable', 'string', 'min:2', 'max:255'],
            'unique_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'uniqueId' => ['nullable', 'string', 'min:2', 'max:255'],
            'android_id' => ['nullable', 'string', 'min:2', 'max:255'],
            'androidId' => ['nullable', 'string', 'min:2', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'deviceName' => ['nullable', 'string', 'max:100'],
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
        $credentials['device_name'] = $credentials['device_name'] ?? $credentials['deviceName'] ?? null;
        $credentials['battery_percentage'] = $this->batteryPercentageFromPayload($credentials);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'This account is inactive.',
            ]);
        }

        $device = null;
        $credentials['device_id'] = $credentials['device_id'] ?: $this->fallbackDeviceIdForLogin($user, $request);

        $otherUserDevice = EmployeeDevice::query()
            ->where('device_id', $credentials['device_id'])
            ->where('employee_id', '!=', $user->id)
            ->first();

        if ($otherUserDevice) {
            throw ValidationException::withMessages([
                'device_id' => 'Already registered with other user. Please contact admin.',
            ]);
        }

        $sameUserOtherDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->where('device_id', '!=', $credentials['device_id'])
            ->first();

        if ($sameUserOtherDevice) {
            throw ValidationException::withMessages([
                'device_id' => 'Already registered with other device. Please contact admin.',
            ]);
        }

        app(SingleLoginService::class)->invalidateOtherLogins((int) $user->id);

        $plainToken = Str::random(80);
        $token = MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $credentials['device_name'] ?? 'mobile',
            'device_id' => $credentials['device_id'],
            'token_hash' => hash('sha256', $plainToken),
        ]);

<<<<<<< HEAD
        if (filled($credentials['device_id'] ?? null)) {
            $device = EmployeeDevice::query()->updateOrCreate(
                [
                    'employee_id' => $user->id,
                    'device_id' => $credentials['device_id'],
                ],
                $this->availableEmployeeDeviceAttributes([
                    'device_name' => $credentials['device_name'] ?? null,
                    'device_type' => $credentials['device_type'] ?? $credentials['deviceType'] ?? null,
                    'brand' => $credentials['brand'] ?? null,
                    'board' => $credentials['board'] ?? null,
                    'sdk_version' => $credentials['sdk_version'] ?? $credentials['sdkVersion'] ?? null,
                    'model' => $credentials['model'] ?? null,
                    'last_seen_at' => now(),
                ])
            );
=======
        $deviceValues = [
            'device_name' => $credentials['device_name'] ?? null,
            'device_type' => $credentials['device_type'] ?? $credentials['deviceType'] ?? null,
            'brand' => $credentials['brand'] ?? null,
            'board' => $credentials['board'] ?? null,
            'sdk_version' => $credentials['sdk_version'] ?? $credentials['sdkVersion'] ?? null,
            'model' => $credentials['model'] ?? null,
            'last_seen_at' => now(),
        ];
        if ($credentials['battery_percentage'] !== null) {
            $deviceValues['battery_percentage'] = $credentials['battery_percentage'];
>>>>>>> 61c89e1176053a0e34b77797d7dda63d0301ad1f
        }

        $device = EmployeeDevice::query()->updateOrCreate(
            [
                'employee_id' => $user->id,
                'device_id' => $credentials['device_id'],
            ],
            $deviceValues
        );

        $activeTokensCount = MobileApiToken::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        return response()->json([
            'message' => 'Login successful.',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'device' => $device ? $this->devicePayload($device) : null,
            'token_id' => $token->id,
            'active_tokens_count' => $activeTokensCount,
        ]);
    }

    public function logout(Request $request)
    {
        $plainToken = $request->bearerToken();

        if ($blockResponse = $this->incompleteDueTasksBlockResponse($request->user(), 'logout')) {
            return $blockResponse;
        }

        if ($plainToken) {
            MobileApiToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function fallbackDeviceIdForLogin(User $user, Request $request): string
    {
        $existingDevice = EmployeeDevice::query()
            ->where('employee_id', $user->id)
            ->latest('last_seen_at')
            ->first();

        if ($existingDevice) {
            return (string) $existingDevice->device_id;
        }

        $fallback = collect([
            $request->input('device_name') ?? $request->input('deviceName') ?? 'mobile',
            $request->input('brand'),
            $request->input('model'),
            $user->id,
        ])->filter()->implode('|');

        return 'legacy-' . sha1($fallback);
    }
}
