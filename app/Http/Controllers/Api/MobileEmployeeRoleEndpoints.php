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

trait MobileEmployeeRoleEndpoints
{
    public function trackEmployees(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $employees = User::query()
            ->with(['roles'])
            ->when($validated['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($validated['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 25));

        $userIds = $employees->getCollection()->pluck('id');
        $attendanceByUser = Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('attendance_date', now()->toDateString())
            ->latest('check_in_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $deviceByUser = EmployeeDevice::query()
            ->whereIn('employee_id', $userIds)
            ->latest('last_seen_at')
            ->get()
            ->unique('employee_id')
            ->keyBy('employee_id');

        $taskEmployeeIdsByEmail = Employee::query()
            ->whereIn('email', $employees->getCollection()->pluck('email')->filter())
            ->pluck('id', 'email');

        $employees->setCollection($employees->getCollection()->map(function (User $user) use ($attendanceByUser, $deviceByUser, $taskEmployeeIdsByEmail) {
            $attendance = $attendanceByUser->get($user->id);
            $device = $deviceByUser->get($user->id);

            return [
                ...$this->userPayload($user),
                'task_employee_id' => $taskEmployeeIdsByEmail->get($user->email),
                'attendance_status' => $attendance && ! $attendance->check_out_at ? 'checked_in' : 'checked_out',
                'today_attendance' => $attendance ? $this->attendancePayload($attendance) : null,
                'latest_location' => $device ? $this->devicePayload($device) : null,
            ];
        }));

        return response()->json($employees);
    }

    public function employees(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'role' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $employees = User::query()
            ->with('roles')
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn($query, string $status) => $query->where('status', $status))
            ->when($validated['role'] ?? null, function ($query, string $role) {
                $query->where(function ($q) use ($role) {
                    $q->where('role', $role)
                        ->orWhereHas('roles', fn($roleQuery) => $roleQuery->where('name', $role));
                });
            })
            ->when($validated['date_from'] ?? null, fn($query) => $query->whereDate('created_at', '>=', $request->date('date_from')->toDateString()))
            ->when($validated['date_to'] ?? null, fn($query) => $query->whereDate('created_at', '<=', $request->date('date_to')->toDateString()))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 15));

        $taskEmployeeIds = $this->taskEmployeeIdsByUsers($employees->getCollection());
        $employees->setCollection($employees->getCollection()->map(fn(User $user) => $this->employeePayload($user, $taskEmployeeIds[$user->id] ?? null)));

        return response()->json([
            'employees' => $employees,
            'roles' => Role::query()
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn(Role $role) => $this->rolePayload($role)),
        ]);
    }

    public function storeEmployee(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-create')) {
            return $forbidden;
        }

        $validated = $this->validateEmployeeData($request);
        $role = Role::query()->where('name', $validated['role'])->firstOrFail();
        $this->authorizeRoleAssignment($request->user(), $role);

        unset($validated['password_confirmation']);
        $validated = $this->handleEmployeeAvatarUpload($request, $validated);

        $employee = User::query()->create($validated);
        $employee->roles()->sync([$role->id]);
        $this->syncTaskEmployeeRecord($employee->fresh());

        return response()->json([
            'message' => 'Employee created successfully.',
            'employee' => $this->employeePayload($employee->fresh('roles')),
        ], 201);
    }

    public function showEmployee(Request $request, User $employee)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-list')) {
            return $forbidden;
        }

        return response()->json($this->employeeDetailPayload($employee));
    }

    public function employeeProfile(Request $request)
    {
        return response()->json($this->employeeDetailPayload($request->user()));
    }

    public function updateEmployee(Request $request, User $employee)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-edit')) {
            return $forbidden;
        }

        $validated = $this->validateEmployeeData($request, $employee);
        $role = Role::query()->where('name', $validated['role'])->firstOrFail();
        $this->authorizeRoleAssignment($request->user(), $role);
        $previousEmail = $employee->email;

        unset($validated['password_confirmation']);
        $validated = $this->handleEmployeeAvatarUpload($request, $validated);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $employee->update($validated);
        $employee->roles()->sync([$role->id]);
        $this->syncTaskEmployeeRecord($employee->fresh(), $previousEmail);

        return response()->json([
            'message' => 'Employee updated successfully.',
            'employee' => $this->employeePayload($employee->fresh('roles')),
        ]);
    }

    public function deleteEmployee(Request $request, User $employee)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'employees-delete')) {
            return $forbidden;
        }

        $employee->update(['status' => 'inactive']);
        $employee->mobileApiTokens()->delete();
        $this->syncTaskEmployeeRecord($employee->fresh());

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    public function permissionContext(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
            'roles' => $this->userRolesPayload($request->user()),
            'permissions' => $this->userPermissionKeys($request->user()),
        ]);
    }

    public function roles(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'roles-list')) {
            return $forbidden;
        }

        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn(Role $role) => $this->rolePayload($role));

        return response()->json(['data' => $roles]);
    }

    public function permissions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'permissions-list')) {
            return $forbidden;
        }

        $permissions = Permission::query()
            ->orderBy('key')
            ->get()
            ->map(fn(Permission $permission) => $this->permissionPayload($permission));

        return response()->json(['data' => $permissions]);
    }
}

