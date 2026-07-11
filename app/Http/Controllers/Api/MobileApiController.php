<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\LocationTracking;
use App\Models\MobileApiToken;
use App\Models\Client;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileApiController extends Controller
{
    private const TASK_TYPES = [
        'general',
        'daily',
        'weekly',
    ];

    private const PAYMENT_MODES = [
        1 => 'Cash',
        2 => 'Bank Transfer',
        3 => 'UPI',
        4 => 'Cheque',
        5 => 'Card',
    ];

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

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

        $plainToken = Str::random(80);
        $token = MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $credentials['device_name'] ?? 'mobile',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'token_id' => $token->id,
        ]);
    }

    public function logout(Request $request)
    {
        $plainToken = $request->bearerToken();

        if ($plainToken) {
            MobileApiToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
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

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $this->attendancePayload($attendance),
        ], 201);
    }

    public function checkOut(Request $request)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
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

        $openAttendance->update([
            'check_out_at' => $checkoutTime,
            'worked_minutes' => $openAttendance->check_in_at->diffInMinutes($checkoutTime),
            'notes' => $notes !== '' ? $notes : null,
        ]);

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $this->attendancePayload($openAttendance->fresh()),
        ]);
    }

    public function attendances(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'attendance-list')) {
            return $forbidden;
        }

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

        if (! blank($validated['user_id'] ?? null)) {
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
            'tracking_interval_seconds' => 60,
            'minimum_distance_meters' => 25,
            'max_accuracy_meters' => 50,
            'mock_location_allowed' => false,
            'history_retention_days' => 90,
        ]);
    }

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
            ->with('permissions')
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

    public function assignTask(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tasks-create')) {
            return $forbidden;
        }

        $validated = $this->validateTaskData($request);

        $task = Task::query()->create($validated);
        $this->createNextRecurringTaskIfNeeded($task);
        $task->load(['project', 'employee']);

        return response()->json([
            'message' => 'Task assigned successfully.',
            'task' => $this->taskPayload($task),
        ], 201);
    }

    public function tasks(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tasks-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'project_id' => ['nullable', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'type' => ['nullable', Rule::in(self::TASK_TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Task::query()->with(['project', 'employee']);

        if (! blank($validated['q'] ?? null)) {
            $search = $validated['q'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('employee', fn($employeeQuery) => $employeeQuery->where('name', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'priority', 'project_id', 'employee_id', 'type'] as $filter) {
            if (! blank($validated[$filter] ?? null)) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (! blank($validated['date_from'] ?? null)) {
            $query->whereDate('due_date', '>=', $request->date('date_from')->toDateString());
        }

        if (! blank($validated['date_to'] ?? null)) {
            $query->whereDate('due_date', '<=', $request->date('date_to')->toDateString());
        }

        $tasks = $query
            ->orderByRaw('COALESCE(due_date, CURRENT_DATE) desc')
            ->orderByDesc('is_important')
            ->orderBy('sort_order')
            ->paginate((int) ($validated['per_page'] ?? 25));

        $tasks->setCollection($tasks->getCollection()->map(fn(Task $task) => $this->taskPayload($task)));

        return response()->json($tasks);
    }

    public function showTask(Request $request, Task $task)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tasks-list')) {
            return $forbidden;
        }

        return response()->json([
            'task' => $this->taskPayload($task->load(['project', 'employee'])),
        ]);
    }

    public function updateTask(Request $request, Task $task)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tasks-edit')) {
            return $forbidden;
        }

        $validated = $this->validateTaskData($request);

        $task->update($validated);
        $this->createNextRecurringTaskIfNeeded($task->fresh());
        $task->load(['project', 'employee']);

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $this->taskPayload($task->fresh(['project', 'employee'])),
        ]);
    }

    public function deleteTask(Request $request, Task $task)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'tasks-delete')) {
            return $forbidden;
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function wallets(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'transfers-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Wallet::query()
            ->where('delete_status', 0)
            ->with(['user', 'client', 'project', 'stage'])
            ->when($validated['client_id'] ?? null, fn($q, $clientId) => $q->where('client_id', $clientId))
            ->when($validated['project_id'] ?? null, fn($q, $projectId) => $q->where('project_id', $projectId));

        if (! blank($validated['date_from'] ?? null)) {
            $query->whereDate('current_date', '>=', $request->date('date_from')->toDateString());
        }

        if (! blank($validated['date_to'] ?? null)) {
            $query->whereDate('current_date', '<=', $request->date('date_to')->toDateString());
        }

        if (! blank($validated['search'] ?? null)) {
            $search = $validated['search'];
            $lower = strtolower($search);
            $matchingPaymentModeIds = collect(self::PAYMENT_MODES)
                ->filter(fn(string $label) => str_contains(strtolower($label), $lower))
                ->keys()
                ->all();

            $query->where(function ($q) use ($search, $lower, $matchingPaymentModeIds) {
                if (str_contains('credited', $lower)) {
                    $q->orWhere('transfer_type', 0);
                }
                if (str_contains('debited', $lower)) {
                    $q->orWhere('transfer_type', 1);
                }
                if ($matchingPaymentModeIds !== []) {
                    $q->orWhereIn('payment_mode', $matchingPaymentModeIds);
                }

                $q->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('stage', fn($stageQuery) => $stageQuery->where('stage_name', 'like', "%{$search}%"));
            });
        }

        $totalAmount = (clone $query)->sum('amount');
        $wallets = $query
            ->latest('current_date')
            ->paginate((int) ($validated['per_page'] ?? 10));

        $wallets->setCollection($wallets->getCollection()->map(fn(Wallet $wallet) => $this->walletPayload($wallet)));

        return response()->json([
            'total_amount' => (int) $totalAmount,
            ...$wallets->toArray(),
        ]);
    }

    public function walletOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'transfers-create')) {
            return $forbidden;
        }

        return response()->json([
            'clients' => Client::query()
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name']),
            'projects' => Project::query()
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->orderBy('name')
                ->get(['id', 'client_id', 'name', 'status']),
            'payment_modes' => self::PAYMENT_MODES,
            'stages' => PaymentStage::query()
                ->orderBy('stage_name')
                ->get(['id', 'stage_name']),
            'wallet_balance' => (float) ($request->user()->wallet ?? 0),
        ]);
    }

    public function transferWallet(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'transfers-create')) {
            return $forbidden;
        }

        $validated = $this->validateWalletData($request);
        $amount = (int) $validated['amount'];
        $user = $request->user();
        $project = Project::query()->findOrFail((int) $validated['project_id']);

        if ((int) $project->client_id !== (int) $validated['client_id']) {
            throw ValidationException::withMessages([
                'project_id' => 'Selected project does not belong to the selected client.',
            ]);
        }

        if ((int) $validated['transfer_type'] === 1 && $amount > (float) ($user->wallet ?? 0)) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is insufficient',
            ]);
        }

        $wallet = DB::transaction(function () use ($validated, $amount, $user) {
            $dateTime = Carbon::parse($validated['current_date'] . ' ' . ($validated['time'] ?? now()->format('H:i')));

            $wallet = Wallet::query()->create([
                'user_id' => $user->id,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'amount' => $amount,
                'payment_mode' => $validated['payment_mode'],
                'transfer_type' => $validated['transfer_type'],
                'stage_id' => $validated['stage_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'current_date' => $dateTime,
                'active_status' => 1,
                'delete_status' => 0,
            ]);

            $balanceService = app(CrmBalanceService::class);

            if ((int) $validated['transfer_type'] === 0) {
                $balanceService->applyProjectIncome((int) $validated['project_id'], $amount);
                $balanceService->adjustUserWallet((int) $user->id, $amount);

                return $wallet;
            }

            $balanceService->reverseProjectIncome((int) $validated['project_id'], $amount);
            $balanceService->adjustUserWallet((int) $user->id, -$amount);

            return $wallet;
        });

        return response()->json([
            'message' => 'Wallet entry saved successfully.',
            'wallet' => $this->walletPayload($wallet->load(['user', 'client', 'project', 'stage'])),
            'wallet_balance' => (float) $user->fresh()->wallet,
        ], 201);
    }

    private function taskEmployeeIdFromUserId(?int $userId): ?int
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
            ->orWhereKey($user->id)
            ->value('id');
    }

    private function validateTaskData(Request $request): array
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(self::TASK_TYPES)],
            'auto_repeat' => ['nullable', 'boolean'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'logged_hours' => ['nullable', 'numeric', 'min:0'],
            'is_important' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_important'] = $request->boolean('is_important');
        $validated['auto_repeat'] = $request->boolean('auto_repeat');
        $validated['completed_at'] = $validated['status'] === 'completed' ? now() : null;

        return $validated;
    }

    private function validateWalletData(Request $request): array
    {
        return $request->validate([
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

    private function createNextRecurringTaskIfNeeded(Task $task): void
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

    private function validateTrackingPayload(Request $request, string $defaultType): array
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:50'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'activity' => ['nullable', 'string', 'max:100'],
            'is_gps_on' => ['nullable', 'boolean'],
            'isGpsOn' => ['nullable', 'boolean'],
            'is_mock_location' => ['nullable', 'boolean'],
            'isMock' => ['nullable', 'boolean'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'batteryPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['check_in', 'travelling', 'still', 'check_out'])],
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
        $validated['type'] = $validated['type'] ?? $defaultType;

        return $validated;
    }

    private function activeAttendance(int $userId): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();
    }

    private function upsertDeviceStatus(int $userId, array $payload): EmployeeDevice
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

    private function createTrackingPoint(Attendance $attendance, array $payload, string $type): LocationTracking
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
            'type' => $type,
            'recorded_at' => isset($payload['recorded_at']) ? Carbon::parse($payload['recorded_at']) : now(),
        ]);
    }

    private function canViewEmployeeTracking(User $user): bool
    {
        return $this->isSuperAdmin($user) || $user->hasPermission('employees-list');
    }

    private function authorizeApiPermission(Request $request, string $permission): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user || (! $this->isSuperAdmin($user) && ! $user->hasPermission($permission))) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return null;
    }

    private function isSuperAdmin(User $user): bool
    {
        return ($user->role ?? null) === 'Super Admin'
            || $user->assignedRoles()->contains('name', 'Super Admin');
    }

    private function userPayload(?User $user): ?array
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
            'permissions' => $this->userPermissionKeys($user),
            'status' => $user->status,
            'wallet' => (float) ($user->wallet ?? 0),
        ];
    }

    private function userRolesPayload(User $user): array
    {
        return $user->assignedRoles()
            ->map(fn(Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
            ])
            ->values()
            ->all();
    }

    private function userPermissionKeys(User $user): array
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

    private function rolePayload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'users_count' => $role->users_count ?? null,
            'permissions' => $role->permissions
                ->map(fn(Permission $permission) => $this->permissionPayload($permission))
                ->values()
                ->all(),
        ];
    }

    private function permissionPayload(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'key' => $permission->key,
        ];
    }

    private function attendancePayload(Attendance $attendance): array
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

    private function devicePayload(EmployeeDevice $device): array
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

    private function trackingPayload(LocationTracking $tracking): array
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

    private function taskPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'project_name' => $task->project?->name,
            'employee_id' => $task->employee_id,
            'employee_name' => $task->employee?->name,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateString(),
            'estimated_hours' => (float) $task->estimated_hours,
            'logged_hours' => (float) $task->logged_hours,
            'is_important' => (bool) $task->is_important,
            'completed_at' => $task->completed_at?->toISOString(),
        ];
    }

    private function walletPayload(Wallet $wallet): array
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
