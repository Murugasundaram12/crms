<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\EmployeeDevice;
use App\Models\MobileApiToken;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeviceManagementController extends Controller
{
    public function index(Request $request): View
    {
        $onlineThresholdSeconds = $this->onlineThresholdSeconds();
        $activeDeviceKeys = MobileApiToken::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereNotNull('device_id')
            ->get(['user_id', 'device_id'])
            ->map(fn (MobileApiToken $token) => $this->deviceLoginKey((int) $token->user_id, (string) $token->device_id))
            ->all();

        $query = EmployeeDevice::query()
            ->with('employee.roles')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('device_id', 'like', "%{$search}%")
                        ->orWhere('device_name', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('last_seen_at');

        $summaryDevices = (clone $query)->get()->map(function (EmployeeDevice $device) use ($onlineThresholdSeconds, $activeDeviceKeys) {
            return [
                'login_status' => in_array($this->deviceLoginKey((int) $device->employee_id, (string) $device->device_id), $activeDeviceKeys, true) ? 'login' : 'logout',
                'online_status' => $device->last_seen_at && $device->last_seen_at->gt(now()->subSeconds($onlineThresholdSeconds)) ? 'online' : 'offline',
            ];
        });

        $devices = $query->paginate((int) $request->input('per_page', 15));

        $devices->setCollection($devices->getCollection()->map(function (EmployeeDevice $device) use ($onlineThresholdSeconds, $activeDeviceKeys) {
            $isLoggedIn = in_array($this->deviceLoginKey((int) $device->employee_id, (string) $device->device_id), $activeDeviceKeys, true);
            $isOnline = $device->last_seen_at && $device->last_seen_at->gt(now()->subSeconds($onlineThresholdSeconds));

            $device->setAttribute('login_status', $isLoggedIn ? 'login' : 'logout');
            $device->setAttribute('online_status', $isOnline ? 'online' : 'offline');

            return $device;
        }));

        return view('pages.device_management.index', [
            'devices' => $devices,
            'onlineThresholdSeconds' => $onlineThresholdSeconds,
            'summary' => [
                'registered' => $summaryDevices->count(),
                'logged_in' => $summaryDevices->where('login_status', 'login')->count(),
                'logged_out' => $summaryDevices->where('login_status', 'logout')->count(),
                'online' => $summaryDevices->where('online_status', 'online')->count(),
            ],
        ]);
    }

    public function destroy(EmployeeDevice $device): RedirectResponse
    {
        $deviceLabel = trim(collect([$device->device_name, $device->device_id])->filter()->implode(' - '));
        MobileApiToken::query()
            ->where('user_id', $device->employee_id)
            ->where('device_id', $device->device_id)
            ->delete();

        $device->delete();

        return redirect()
            ->route('device-management.index')
            ->with('success', "Device {$deviceLabel} deleted successfully.");
    }

    private function onlineThresholdSeconds(): int
    {
        $setting = AppSetting::query()->where('key', 'online_threshold_seconds')->first();
        if ($setting && $setting->value !== null && $setting->value !== '') {
            return max(60, (int) $setting->value);
        }

        $offlineCheckTime = AppSetting::query()->where('key', 'offline_check_time')->first();
        $offlineCheckType = AppSetting::query()->where('key', 'offline_check_time_type')->first();
        $value = (int) ($offlineCheckTime?->value ?: 15);
        $type = (string) ($offlineCheckType?->value ?: 'minutes');

        return max(60, match ($type) {
            'seconds' => $value,
            'hours' => $value * 3600,
            default => $value * 60,
        });
    }

    private function deviceLoginKey(int $employeeId, string $deviceId): string
    {
        return $employeeId . ':' . $deviceId;
    }
}
