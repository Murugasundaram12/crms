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
        $activeTokenUserIds = MobileApiToken::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('user_id')
            ->map(fn($userId) => (int) $userId)
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

        $summaryDevices = (clone $query)->get()->map(function (EmployeeDevice $device) use ($onlineThresholdSeconds, $activeTokenUserIds) {
            return [
                'login_status' => in_array((int) $device->employee_id, $activeTokenUserIds, true) ? 'login' : 'logout',
                'online_status' => $device->last_seen_at && $device->last_seen_at->gt(now()->subSeconds($onlineThresholdSeconds)) ? 'online' : 'offline',
            ];
        });

        $devices = $query->paginate((int) $request->input('per_page', 15));

        $devices->setCollection($devices->getCollection()->map(function (EmployeeDevice $device) use ($onlineThresholdSeconds, $activeTokenUserIds) {
            $isLoggedIn = in_array((int) $device->employee_id, $activeTokenUserIds, true);
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

        $device->delete();

        return redirect()
            ->route('device-management.index')
            ->with('success', "Device {$deviceLabel} deleted successfully.");
    }

    private function onlineThresholdSeconds(): int
    {
        $setting = AppSetting::query()->where('key', 'online_threshold_seconds')->first();

        return max(60, (int) ($setting?->value ?? 1800));
    }
}
