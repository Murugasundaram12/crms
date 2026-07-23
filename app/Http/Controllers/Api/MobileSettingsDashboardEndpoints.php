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

trait MobileSettingsDashboardEndpoints
{
    public function getAppSettings()
    {
        return response()->json([
            'message' => 'App settings fetched successfully.',
            'data' => $this->appSettingsPayload(),
        ]);
    }

    public function getModuleSettings()
    {
        return response()->json([
            'message' => 'Module settings fetched successfully.',
            'data' => $this->moduleSettingsPayload(),
        ]);
    }

    public function getMapSettings()
    {
        return response()->json([
            'message' => 'Map settings fetched successfully.',
            'data' => $this->mapSettingsPayload(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $taskEmployeeId = $this->taskEmployeeIdFromUserId($user->id);
        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->latest('check_in_at')
            ->first();
        $activeAttendance = $this->activeAttendance($user->id);

        return response()->json([
            'user' => $this->userPayload($user),
            'today_attendance' => $todayAttendance ? $this->attendancePayload($todayAttendance) : null,
            'active_attendance' => $activeAttendance ? $this->attendancePayload($activeAttendance) : null,
            'my_tasks' => [
                'pending' => $taskEmployeeId ? Task::query()->where('employee_id', $taskEmployeeId)->where('status', 'pending')->count() : 0,
                'in_progress' => $taskEmployeeId ? Task::query()->where('employee_id', $taskEmployeeId)->where('status', 'in_progress')->count() : 0,
                'completed' => $taskEmployeeId ? Task::query()->where('employee_id', $taskEmployeeId)->where('status', 'completed')->count() : 0,
            ],
            'admin_counts' => [
                'clients' => $this->canViewAllAppData($user) ? Client::query()->count() : null,
                'projects' => $this->canViewAllAppData($user) ? Project::query()->count() : null,
                'employees' => $this->canViewAllAppData($user) ? User::query()->count() : null,
                'expenses_total' => $this->canViewAllAppData($user) ? (float) Expense::query()->sum('amount') : null,
            ],
            'map_settings' => $this->mapSettingsPayload(),
        ]);
    }

    public function appOptions()
    {
        return response()->json([
            'task_types' => self::TASK_TYPES,
            'task_statuses' => ['pending', 'in_progress', 'completed', 'blocked'],
            'priorities' => ['low', 'medium', 'high'],
            'employee_statuses' => ['active', 'inactive'],
            'client_statuses' => ['enquiry', 'active', 'inactive'],
            'project_statuses' => ['planning', 'active', 'on_hold', 'completed', 'cancelled'],
            'payment_statuses' => ['pending', 'paid', 'overdue', 'partial'],
            'payment_methods' => \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'expense_payment_modes' => \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'leave_statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }
}

