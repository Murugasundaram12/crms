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

class MobileApiController extends Controller
{
    protected const TASK_TYPES = [
        'general',
        'daily',
        'weekly',
    ];

    protected const PAYMENT_MODES = [
        1 => 'Cash',
        2 => 'Bank Transfer',
        3 => 'UPI',
        4 => 'Cheque',
        5 => 'Card',
    ];

    protected function taskEmployeeIdFromUserId(?int $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return null;
        }

        return Employee::query()
            ->where('email', $user->email)
            ->orWhere('id', $user->id)
            ->value('id');
    }

    protected function incompleteDueTasksForUser(User $user)
    {
        $taskEmployeeId = $this->taskEmployeeIdFromUserId($user->id);

        if (! $taskEmployeeId) {
            return collect();
        }

        return Task::query()
            ->with(['project', 'employee'])
            ->where('employee_id', $taskEmployeeId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->toDateString())
            ->where('status', '!=', 'completed')
            ->orderByRaw('due_date asc')
            ->orderByDesc('is_important')
            ->orderBy('sort_order')
            ->get();
    }

    protected function incompleteDueTasksBlockResponse(User $user, string $action): ?\Illuminate\Http\JsonResponse
    {
        $pendingTasks = $this->incompleteDueTasksForUser($user);

        if ($pendingTasks->isEmpty()) {
            return null;
        }

        return response()->json([
            'message' => "Due tasks are not completed. Complete due tasks before {$action}.",
            'pending_tasks_count' => $pendingTasks->count(),
            'tasks' => $pendingTasks
                ->map(fn(Task $task) => $this->taskPayload($task))
                ->values(),
        ], 409);
    }

    protected function resolveTaskEmployeeId(?int $employeeId, ?int $userId = null): ?int
    {
        if ($userId) {
            return $this->taskEmployeeIdFromUserId($userId);
        }

        if (! $employeeId) {
            return null;
        }

        if (Employee::query()->whereKey($employeeId)->exists()) {
            return $employeeId;
        }

        return $this->taskEmployeeIdFromUserId($employeeId);
    }

    protected function syncTaskEmployeeRecord(User $user, ?string $previousEmail = null): Employee
    {
        $employee = Employee::query()->where('email', $user->email)->first();

        if (! $employee && $previousEmail && $previousEmail !== $user->email) {
            $employee = Employee::query()->where('email', $previousEmail)->first();
        }

        $employee ??= new Employee();

        $employee->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'designation' => $user->designation,
            'role' => $user->role,
            'address' => $user->address,
            'hourly_rate' => $user->hourly_rate ?? 0,
            'hire_date' => $user->hire_date,
            'status' => $user->status ?? 'active',
            'avatar' => $user->avatar,
            'password' => $user->password,
        ]);

        $employee->save();

        return $employee;
    }

    protected function validateTaskData(Request $request, ?Task $task = null): array
    {
        $required = $task ? 'sometimes' : 'required';

        $validated = $request->validate([
            'project_id' => [$required, 'exists:projects,id'],
            'employee_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [$required, Rule::in(self::TASK_TYPES)],
            'auto_repeat' => ['nullable', 'boolean'],
            'priority' => [$required, Rule::in(['low', 'medium', 'high'])],
            'status' => [$required, Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'logged_hours' => ['nullable', 'numeric', 'min:0'],
            'is_important' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $taskType = $validated['type'] ?? $task?->type;

        if ($request->boolean('auto_repeat') && ! in_array($taskType, ['daily', 'weekly'], true)) {
            throw ValidationException::withMessages([
                'auto_repeat' => 'Auto repeat is available only for daily or weekly tasks.',
            ]);
        }

        $taskEmployeeId = $this->resolveTaskEmployeeId(
            isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        );

        if (($validated['employee_id'] ?? null) || ($validated['user_id'] ?? null)) {
            if (! $taskEmployeeId) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Selected employee could not be matched to the task employee records.',
                ]);
            }

            $validated['employee_id'] = $taskEmployeeId;
        }

        unset($validated['user_id']);

        if (! $task || $request->has('is_important')) {
            $validated['is_important'] = $request->boolean('is_important');
        }

        if (! $task || $request->has('auto_repeat') || array_key_exists('type', $validated)) {
            $validated['auto_repeat'] = in_array($taskType, ['daily', 'weekly'], true)
                && ($request->has('auto_repeat') ? $request->boolean('auto_repeat') : (bool) $task?->auto_repeat);
        }

        if (array_key_exists('status', $validated)) {
            $validated['completed_at'] = $validated['status'] === 'completed' ? now() : null;
        }

        return $validated;
    }

    protected function validateOwnTaskUpdateData(Request $request, Task $task): array
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'logged_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('status', $validated)) {
            if ($validated['status'] === 'completed') {
                if ($task->status !== 'completed') {
                    $validated['completed_at'] = now();
                }
            } else {
                $validated['completed_at'] = null;
            }
        } elseif ($task->status === 'completed') {
            unset($validated['completed_at']);
        }

        return $validated;
    }

    protected function validateEmployeeData(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee?->id),
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    protected function validateClientData(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client?->id)],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['enquiry', 'active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function validateProjectData(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'project_code' => ['required', 'string', 'max:50', Rule::unique('projects', 'project_code')->ignore($project?->id)],
            'client_id' => ['required', 'exists:clients,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['planning', 'active', 'on_hold', 'completed', 'cancelled'])],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:500'],
        ]);
    }

    protected function validateExpenseData(Request $request): array
    {
        $request->merge([
            'paid_amt' => $request->input('paid_amt', $request->input('paid_amount', 0)),
            'current_date' => $request->input('current_date', $request->input('expense_date', now()->toDateString())),
        ]);

        return $request->validate([
            'main_category_id' => ['nullable', 'integer', 'exists:main_categories,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_amt' => ['required', 'integer', 'min:0'],
            'current_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'payment_mode' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);
    }

    protected function authorizeRoleAssignment(User $actor, Role $role): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }

        $protectedRoles = ['Super Admin', 'Manager'];

        if (in_array($role->name, $protectedRoles, true)) {
            throw ValidationException::withMessages([
                'role' => 'You do not have permission to assign this role.',
            ]);
        }
    }

    protected function handleEmployeeAvatarUpload(Request $request, array $validated): array
    {
        if (! $request->hasFile('avatar')) {
            unset($validated['avatar']);

            return $validated;
        }

        $file = $request->file('avatar');
        $fileName = now()->format('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $destination = public_path('images');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $fileName);
        $validated['avatar'] = 'images/' . $fileName;

        return $validated;
    }

    protected function normalizeOptionalTrackingPayload(array $validated, string $type): array
    {
        $isGpsOn = $validated['is_gps_on'] ?? $validated['isGpsOn'] ?? true;
        $isMock = $validated['is_mock_location'] ?? $validated['isMock'] ?? false;

        if (! $isGpsOn) {
            throw ValidationException::withMessages([
                'is_gps_on' => 'GPS must be enabled.',
            ]);
        }

        if ($isMock) {
            throw ValidationException::withMessages([
                'is_mock_location' => 'Mock location is not allowed.',
            ]);
        }

        return [
            'device_id' => $validated['device_id'] ?? 'default',
            'device_name' => $validated['device_name'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? 0,
            'speed' => $validated['speed'] ?? null,
            'activity' => $validated['activity'] ?? null,
            'is_gps_on' => (bool) $isGpsOn,
            'is_mock_location' => (bool) $isMock,
            'battery_percentage' => $validated['battery_percentage'] ?? $validated['batteryPercentage'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? null,
            'type' => $this->normalizeTrackingType($type),
        ];
    }

    protected function normalizeTrackingType(string $type): string
    {
        return match ($type) {
            'check_in' => 'checked_in',
            'check_out' => 'checked_out',
            default => $type,
        };
    }

    protected function validateWalletData(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'exists:users,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'integer', 'in:' . implode(',', array_keys(self::PAYMENT_MODES))],
            'transfer_type' => ['required', 'integer', 'in:0,1'],
            'stage_id' => ['nullable', 'exists:payment_stages,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'current_date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    protected function resolveWalletUser(array $validated, User $fallbackUser): User
    {
        if (! blank($validated['user_id'] ?? null)) {
            return User::query()->findOrFail((int) $validated['user_id']);
        }

        if (! blank($validated['employee_id'] ?? null)) {
            $employeeId = (int) $validated['employee_id'];

            $user = User::query()->find($employeeId);

            if ($user) {
                return $user;
            }

            $employee = Employee::query()->find($employeeId);

            if ($employee && filled($employee->email)) {
                $user = User::query()->where('email', $employee->email)->first();

                if ($user) {
                    return $user;
                }
            }

            throw ValidationException::withMessages([
                'employee_id' => 'Selected employee could not be matched to a user wallet.',
            ]);
        }

        return $fallbackUser;
    }

    protected function createNextRecurringTaskIfNeeded(Task $task): void
    {
        if (! $task->auto_repeat || ! in_array($task->type, ['daily', 'weekly'], true) || $task->status !== 'completed') {
            return;
        }

        if (! $task->due_date) {
            return;
        }

        $nextDueDate = match ($task->type) {
            'daily' => $task->due_date->copy()->addDay(),
            'weekly' => $task->due_date->copy()->addWeek(),
            default => null,
        };

        if (! $nextDueDate) {
            return;
        }

        $alreadyExists = Task::query()
            ->where('recurring_source_id', $task->id)
            ->whereDate('due_date', $nextDueDate)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        Task::query()->create([
            'project_id' => $task->project_id,
            'employee_id' => $task->employee_id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'auto_repeat' => true,
            'recurring_source_id' => $task->id,
            'priority' => $task->priority,
            'status' => 'pending',
            'due_date' => $nextDueDate,
            'completed_at' => null,
            'estimated_hours' => $task->estimated_hours,
            'logged_hours' => 0,
            'is_important' => $task->is_important,
            'sort_order' => $task->sort_order,
        ]);
    }

    protected function validateTrackingPayload(Request $request, string $defaultType): array
    {
        $maxAccuracyMeters = $this->settingValue('max_accuracy_meters', 50);

        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:' . $maxAccuracyMeters],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['checked_in', 'check_in', 'travelling', 'still', 'checked_out', 'check_out'])],
        ]);

        $isGpsOn = $validated['is_gps_on'] ?? $validated['isGpsOn'] ?? true;
        $isMock = $validated['is_mock_location'] ?? $validated['isMock'] ?? false;

        if (! $isGpsOn) {
            throw ValidationException::withMessages([
                'is_gps_on' => 'GPS must be enabled.',
            ]);
        }

        if ($isMock) {
            throw ValidationException::withMessages([
                'is_mock_location' => 'Mock location is not allowed.',
            ]);
        }

        $validated['is_gps_on'] = (bool) $isGpsOn;
        $validated['is_mock_location'] = (bool) $isMock;
        $validated['battery_percentage'] = $validated['battery_percentage'] ?? $validated['batteryPercentage'] ?? null;
        $validated['type'] = $this->normalizeTrackingType($validated['type'] ?? $defaultType);

        return $validated;
    }

    protected function activeAttendance(int $userId): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();
    }

    protected function upsertDeviceStatus(int $userId, array $payload): EmployeeDevice
    {
        $deviceId = $payload['device_id'] ?? 'default';

        return EmployeeDevice::query()->updateOrCreate(
            [
                'employee_id' => $userId,
                'device_id' => $deviceId,
            ],
            [
                'device_name' => $payload['device_name'] ?? null,
                'latitude' => $payload['latitude'],
                'longitude' => $payload['longitude'],
                'accuracy' => $payload['accuracy'] ?? null,
                'speed' => $payload['speed'] ?? null,
                'activity' => $payload['activity'] ?? null,
                'is_gps_on' => $payload['is_gps_on'],
                'is_mock_location' => $payload['is_mock_location'],
                'battery_percentage' => $payload['battery_percentage'] ?? null,
                'last_seen_at' => isset($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now(),
            ]
        );
    }

    protected function createTrackingPoint(Attendance $attendance, array $payload, string $type): LocationTracking
    {
        return LocationTracking::query()->create([
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->user_id,
            'device_id' => $payload['device_id'] ?? 'default',
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
            'accuracy' => $payload['accuracy'] ?? null,
            'speed' => $payload['speed'] ?? null,
            'activity' => $payload['activity'] ?? null,
            'is_gps_on' => $payload['is_gps_on'],
            'is_mock_location' => $payload['is_mock_location'],
            'battery_percentage' => $payload['battery_percentage'] ?? null,
            'type' => $this->normalizeTrackingType($type),
            'recorded_at' => isset($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now(),
        ]);
    }

    protected function canViewEmployeeTracking(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->hasPermission('employees-list');
    }

    protected function appSettingsPayload(): array
    {
        return [
            'app_version' => $this->settingValue('app_version', '1.0.0'),
            'minimum_supported_version' => $this->settingValue('minimum_supported_version', '1.0.0'),
            'force_update' => $this->settingValue('force_update', false),
            'privacy_policy_url' => $this->settingValue('privacy_policy_url', ''),
            'tracking_interval_seconds' => $this->settingValue('tracking_interval_seconds', 60),
            'minimum_distance_meters' => $this->settingValue('minimum_distance_meters', 25),
            'max_accuracy_meters' => $this->settingValue('max_accuracy_meters', 50),
            'mock_location_allowed' => $this->settingValue('mock_location_allowed', false),
            'offline_tracking_enabled' => $this->settingValue('offline_tracking_enabled', true),
            'attendance_time_type' => $this->settingValue('attendance_time_type', 'server_time'),
            'server_time' => now()->toISOString(),
            'timezone' => config('app.timezone'),
        ];
    }

    protected function moduleSettingsPayload(): array
    {
        return [
            'attendance' => [
                'enabled' => $this->settingValue('attendance_enabled', true),
                'check_in_enabled' => $this->settingValue('check_in_enabled', true),
                'check_out_enabled' => $this->settingValue('check_out_enabled', true),
                'time_type' => $this->settingValue('attendance_time_type', 'server_time'),
                'geofence_enabled' => $this->settingValue('geofence_enabled', false),
                'geofence_radius_meters' => $this->settingValue('geofence_radius_meters', 100),
                'qr_attendance_enabled' => $this->settingValue('qr_attendance_enabled', false),
                'ip_attendance_enabled' => $this->settingValue('ip_attendance_enabled', false),
                'allowed_ips' => $this->settingValue('allowed_attendance_ips', []),
            ],
            'tracking' => [
                'enabled' => $this->settingValue('tracking_enabled', true),
                'background_tracking_enabled' => $this->settingValue('tracking_enabled', true),
                'offline_tracking_enabled' => $this->settingValue('offline_tracking_enabled', true),
                'interval_seconds' => $this->settingValue('tracking_interval_seconds', 60),
                'minimum_distance_meters' => $this->settingValue('minimum_distance_meters', 25),
                'max_accuracy_meters' => $this->settingValue('max_accuracy_meters', 50),
                'mock_location_allowed' => $this->settingValue('mock_location_allowed', false),
            ],
            'modules' => [
                'tasks' => $this->settingValue('tasks_enabled', true),
                'expenses' => $this->settingValue('expenses_enabled', true),
                'wallet' => $this->settingValue('wallet_enabled', true),
                'leave_requests' => $this->settingValue('leave_requests_enabled', true),
            ],
        ];
    }

    protected function mapSettingsPayload(): array
    {
        return [
            'center_latitude' => $this->settingValue('map_center_latitude', 11.016844),
            'center_longitude' => $this->settingValue('map_center_longitude', 76.955832),
            'zoom_level' => $this->settingValue('map_zoom_level', 12),
            'google_maps_api_key' => config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY', '')),
        ];
    }

    protected function settingValue(string $key, mixed $default): mixed
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $setting = AppSetting::query()
            ->where('key', $key)
            ->where('is_public', true)
            ->first();

        if (! $setting) {
            return $default;
        }

        return $this->castSettingValue($setting->value, $setting->type, $default);
    }

    protected function castSettingValue(?string $value, string $type, mixed $default): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true) ?? $default,
            default => $value,
        };
    }

    protected function canUseApiPermission(?User $user, string $permission): bool
    {
        return $user && ($this->isSuperAdmin($user) || $user->hasPermission($permission));
    }

    protected function isOwnTask(User $user, Task $task): bool
    {
        $taskEmployeeId = $this->taskEmployeeIdFromUserId($user->id);

        return $taskEmployeeId !== null && (int) $task->employee_id === (int) $taskEmployeeId;
    }

    protected function authorizeApiPermission(Request $request, string $permission): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $this->canUseApiPermission($user, $permission)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return null;
    }

    protected function isSuperAdmin(User $user): bool
    {
        return ($user->role ?? null) === 'Super Admin'
            || $user->assignedRoles()->contains('name', 'Super Admin');
    }

    protected function userPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'designation' => $user->designation,
            'role' => $user->role,
            'roles' => $this->userRolesPayload($user),
            'status' => $user->status,
            'wallet' => (float) ($user->wallet ?? 0),
        ];
    }

    protected function employeePayload(User $user, ?int $taskEmployeeId = null): array
    {
        return [
            ...$this->userPayload($user),
            'task_employee_id' => $taskEmployeeId ?? $this->taskEmployeeIdFromUserId($user->id),
            'address' => $user->address,
            'hourly_rate' => $user->hourly_rate !== null ? (float) $user->hourly_rate : null,
            'hire_date' => $user->hire_date?->toDateString(),
            'avatar' => $user->avatar,
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    protected function taskEmployeeIdsByUsers($users): array
    {
        $userIds = $users->pluck('id')->filter()->values();
        $emails = $users->pluck('email')->filter()->values();

        $byId = Employee::query()
            ->whereIn('id', $userIds)
            ->pluck('id', 'id');

        $byEmail = Employee::query()
            ->whereIn('email', $emails)
            ->pluck('id', 'email');

        return $users
            ->mapWithKeys(fn(User $user) => [
                $user->id => $byId->get($user->id) ?? $byEmail->get($user->email),
            ])
            ->all();
    }

    protected function employeeDetailPayload(User $employee): array
    {
        $employee->load('roles.permissions');
        $expensesPerPage = max(1, min((int) request('expenses_per_page', 10), 100));
        $attendancePerPage = max(1, min((int) request('attendance_per_page', 10), 100));

        $expenses = Expense::query()
            ->with(['project', 'mainCategory', 'category'])
            ->where('user_id', $employee->id)
            ->latest('current_date')
            ->paginate($expensesPerPage, ['*'], 'expenses_page');

        $expenses->setCollection($expenses->getCollection()->map(fn(Expense $expense) => $this->employeeExpensePayload($expense)));

        $attendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->latest('attendance_date')
            ->paginate($attendancePerPage, ['*'], 'attendance_page');

        $attendances->setCollection($attendances->getCollection()->map(fn(Attendance $attendance) => $this->attendancePayload($attendance)));

        $workedMinutes = (int) Attendance::query()
            ->where('user_id', $employee->id)
            ->where('attendance_date', '>=', now()->subDays(30)->toDateString())
            ->sum('worked_minutes');

        return [
            'employee' => $this->employeePayload($employee),
            'expenses' => $expenses,
            'attendances' => $attendances,
            'stats' => [
                'expense_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('amount'),
                'paid_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('paid_amt'),
                'unpaid_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('unpaid_amt'),
                'worked_hours' => intdiv($workedMinutes, 60),
                'worked_minutes' => $workedMinutes % 60,
            ],
        ];
    }

    protected function userRolesPayload(User $user): array
    {
        $roles = $user->relationLoaded('roles')
            ? $user->roles
            : $user->roles()->get();

        if ($roles->isEmpty() && filled($user->role)) {
            $roles = Role::query()
                ->where('name', $user->role)
                ->get();
        }

        return $roles
            ->map(fn(Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
            ])
            ->values()
            ->all();
    }

    protected function userPermissionKeys(User $user): array
    {
        if ($this->isSuperAdmin($user)) {
            return Permission::query()
                ->whereNotNull('key')
                ->orderBy('key')
                ->pluck('key')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $user->assignedRoles()
            ->flatMap(fn(Role $role) => $role->permissions->pluck('key'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function rolePayload(Role $role, bool $withPermissions = false): array
    {
        $payload = [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'users_count' => $role->users_count ?? null,
        ];

        if ($withPermissions) {
            $payload['permissions'] = $role->permissions
                ->map(fn(Permission $permission) => $this->permissionPayload($permission))
                ->values()
                ->all();
        }

        return $payload;
    }

    protected function permissionPayload(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'key' => $permission->key,
        ];
    }

    protected function attendancePayload(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'attendance_date' => $attendance->attendance_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toISOString(),
            'check_out_at' => $attendance->check_out_at?->toISOString(),
            'worked_minutes' => $attendance->worked_minutes,
            'status' => $attendance->status,
            'notes' => $attendance->notes,
        ];
    }

    protected function employeeExpensePayload(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'user_id' => $expense->user_id,
            'project_id' => $expense->project_id,
            'project_name' => $expense->project?->name,
            'main_category_id' => $expense->main_category_id,
            'main_category_name' => $expense->mainCategory?->name,
            'category_id' => $expense->category_id,
            'category_name' => $expense->category?->name,
            'amount' => (int) $expense->amount,
            'paid_amt' => (int) $expense->paid_amt,
            'unpaid_amt' => (int) $expense->unpaid_amt,
            'extra_amt' => (int) $expense->extra_amt,
            'payment_mode' => $expense->payment_mode,
            'payment_mode_label' => $expense->payment_mode_label,
            'description' => $expense->description,
            'image' => $expense->image,
            'current_date' => $expense->current_date?->toISOString(),
        ];
    }

    protected function clientPayload(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'company_name' => $client->company_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'city' => $client->city,
            'state' => $client->state,
            'country' => $client->country,
            'status' => $client->status,
            'notes' => $client->notes,
            'projects_count' => $client->projects_count ?? null,
            'payments_count' => $client->payments_count ?? null,
            'created_at' => $client->created_at?->toISOString(),
        ];
    }

    protected function projectPayload(Project $project, bool $withDetails = false): array
    {
        $payload = [
            'id' => $project->id,
            'project_code' => $project->project_code,
            'client_id' => $project->client_id,
            'client_name' => $project->client?->name,
            'manager_id' => $project->manager_id,
            'manager_name' => $project->manager?->name,
            'name' => $project->name,
            'description' => $project->description,
            'type' => $project->type,
            'priority' => $project->priority,
            'status' => $project->status,
            'progress' => (int) ($project->progress ?? 0),
            'start_date' => $project->start_date?->toDateString(),
            'end_date' => $project->end_date?->toDateString(),
            'location' => $project->location,
            'advance_amt' => (float) ($project->advance_amt ?? 0),
            'profit' => (float) ($project->profit ?? 0),
            'tasks_count' => $project->tasks_count ?? null,
            'budget' => (float) $project->budget,
            'spent' => (float) $project->spent,
        ];

        if ($withDetails) {
            $payload['tasks'] = $project->tasks->map(fn(Task $task) => $this->taskPayload($task))->values();
            $payload['payments'] = $project->payments->map(fn(Payment $payment) => $this->paymentPayload($payment))->values();
            $payload['expenses'] = $project->expenses->map(fn(Expense $expense) => $this->employeeExpensePayload($expense))->values();
        }

        return $payload;
    }

    protected function paymentPayload(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'payment_code' => $payment->payment_code,
            'transaction_id' => $payment->transaction_id,
            'client_id' => $payment->client_id,
            'client_name' => $payment->client?->name,
            'project_id' => $payment->project_id,
            'project_name' => $payment->project?->name,
            'quotation_id' => $payment->quotation_id,
            'quotation_number' => $payment->quotation?->quotation_number,
            'stage_id' => $payment->stage_id,
            'stage_name' => $payment->stage?->stage_name,
            'payment_method' => $payment->payment_method ?? $payment->method,
            'amount' => (float) $payment->amount,
            'due_date' => $payment->due_date?->toDateString(),
            'payment_date' => $payment->payment_date?->toISOString(),
            'status' => $payment->status,
            'notes' => $payment->notes,
        ];
    }

    protected function paymentStagePayload(PaymentStage $stage): array
    {
        return [
            'id' => $stage->id,
            'stage_name' => $stage->stage_name,
            'project_id' => $stage->project_id,
        ];
    }

    protected function leaveRequestPayload(LeaveRequest $leaveRequest): array
    {
        return [
            'id' => $leaveRequest->id,
            'user_id' => $leaveRequest->user_id,
            'user_name' => $leaveRequest->user?->name,
            'leave_type_id' => $leaveRequest->leave_type_id,
            'leave_type_name' => $leaveRequest->leaveType?->name,
            'from_date' => $leaveRequest->from_date?->toDateString(),
            'to_date' => $leaveRequest->to_date?->toDateString(),
            'remarks' => $leaveRequest->remarks,
            'document' => $leaveRequest->document,
            'status' => $leaveRequest->status,
            'approved_by_id' => $leaveRequest->approved_by_id,
            'approved_by_name' => $leaveRequest->approvedBy?->name,
            'approved_at' => $leaveRequest->approved_at?->toISOString(),
            'approver_remarks' => $leaveRequest->approver_remarks,
        ];
    }

    protected function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'main_category_id' => $category->main_category_id,
            'main_category_name' => $category->mainCategory?->name,
        ];
    }

    protected function mainCategoryPayload(MainCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'status' => $category->status,
            'categories' => $category->relationLoaded('categories')
                ? $category->categories->map(fn(Category $child) => ['id' => $child->id, 'name' => $child->name])->values()
                : [],
        ];
    }

    protected function vendorPayload(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'address' => $vendor->address,
            'phone' => $vendor->phone,
            'advance_amount' => (float) ($vendor->advance_amount ?? $vendor->advance_amt ?? 0),
        ];
    }

    protected function labourRolePayload(LabourRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'salary_type' => $role->salary_type,
            'salary' => (float) ($role->salary ?? 0),
        ];
    }

    protected function labourPayload(Labour $labour): array
    {
        return [
            'id' => $labour->id,
            'name' => $labour->name,
            'job_title' => $labour->job_title,
            'phone' => $labour->phone ?? $labour->phone_number,
            'labour_role_id' => $labour->labour_role_id,
            'labour_role_name' => $labour->labourRole?->name,
            'gender' => $labour->gender,
            'salary' => (float) ($labour->salary ?? 0),
            'advance_amt' => (float) ($labour->advance_amt ?? 0),
        ];
    }

    protected function devicePayload(EmployeeDevice $device): array
    {
        return [
            'id' => $device->id,
            'employee_id' => $device->employee_id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'latitude' => $device->latitude !== null ? (float) $device->latitude : null,
            'longitude' => $device->longitude !== null ? (float) $device->longitude : null,
            'accuracy' => $device->accuracy !== null ? (float) $device->accuracy : null,
            'speed' => $device->speed !== null ? (float) $device->speed : null,
            'activity' => $device->activity,
            'is_gps_on' => (bool) $device->is_gps_on,
            'is_mock_location' => (bool) $device->is_mock_location,
            'battery_percentage' => $device->battery_percentage,
            'last_seen_at' => $device->last_seen_at?->toISOString(),
        ];
    }

    protected function trackingPayload(LocationTracking $tracking): array
    {
        return [
            'id' => $tracking->id,
            'attendance_id' => $tracking->attendance_id,
            'employee_id' => $tracking->employee_id,
            'device_id' => $tracking->device_id,
            'latitude' => (float) $tracking->latitude,
            'longitude' => (float) $tracking->longitude,
            'accuracy' => $tracking->accuracy !== null ? (float) $tracking->accuracy : null,
            'speed' => $tracking->speed !== null ? (float) $tracking->speed : null,
            'activity' => $tracking->activity,
            'is_gps_on' => (bool) $tracking->is_gps_on,
            'is_mock_location' => (bool) $tracking->is_mock_location,
            'battery_percentage' => $tracking->battery_percentage,
            'type' => $tracking->type,
            'recorded_at' => $tracking->recorded_at?->toISOString(),
        ];
    }

    protected function taskPayload(Task $task): array
    {
        $autoRepeat = (bool) $task->auto_repeat && in_array($task->type, ['daily', 'weekly'], true);

        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'employee_id' => $task->employee_id,
            'employee_name' => $task->employee?->name,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'auto_repeat' => $autoRepeat,
            'recurring_source_id' => $task->recurring_source_id,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateString(),
            'estimated_hours' => (float) $task->estimated_hours,
            'logged_hours' => (float) $task->logged_hours,
            'is_important' => (bool) $task->is_important,
            'completed_at' => $task->completed_at?->toISOString(),
        ];
    }

    protected function walletPayload(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'user_name' => $wallet->user?->name,
            'client_id' => $wallet->client_id,
            'client_name' => $wallet->client?->name,
            'project_id' => $wallet->project_id,
            'project_name' => $wallet->project?->name,
            'amount' => (int) $wallet->amount,
            'payment_mode' => $wallet->payment_mode,
            'payment_mode_name' => self::PAYMENT_MODES[(int) $wallet->payment_mode] ?? null,
            'transfer_type' => (int) $wallet->transfer_type,
            'transfer_type_name' => (int) $wallet->transfer_type === 0 ? 'Credited' : 'Debited',
            'stage_id' => $wallet->stage_id,
            'stage_name' => $wallet->stage?->stage_name,
            'description' => $wallet->description,
            'current_date' => $wallet->current_date?->toISOString(),
            'active_status' => (int) $wallet->active_status,
            'delete_status' => (int) $wallet->delete_status,
        ];
    }
}

