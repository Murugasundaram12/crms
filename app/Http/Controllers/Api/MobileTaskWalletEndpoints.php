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

trait MobileTaskWalletEndpoints
{
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
        $ownTaskEmployeeId = $this->taskEmployeeIdFromUserId($request->user()->id);
        $canViewAll = $this->canViewAllAppData($request->user());

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'project_id' => ['nullable', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'type' => ['nullable', Rule::in(self::TASK_TYPES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Task::query()->with(['project', 'employee']);

        if (! $canViewAll && ! $ownTaskEmployeeId) {
            $emptyTasks = Task::query()
                ->whereRaw('1 = 0')
                ->paginate((int) ($validated['per_page'] ?? 25));

            return response()->json($emptyTasks);
        }

        if (! $canViewAll) {
            $query->where('employee_id', $ownTaskEmployeeId);
        }

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
                if ($filter === 'employee_id' && ! $canViewAll) {
                    continue;
                }

                $query->where($filter, $validated[$filter]);
            }
        }

        $fromDate = $validated['date_from'] ?? $validated['from'] ?? null;
        $toDate = $validated['date_to'] ?? $validated['to'] ?? null;

        if (! blank($fromDate)) {
            $query->whereDate('due_date', '>=', Carbon::parse($fromDate)->toDateString());
        }

        if (! blank($toDate)) {
            $query->whereDate('due_date', '<=', Carbon::parse($toDate)->toDateString());
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
        if (! $this->canViewAllAppData($request->user()) && ! $this->isOwnTask($request->user(), $task)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'task' => $this->taskPayload($task->load(['project', 'employee'])),
        ]);
    }

    public function updateTask(Request $request, Task $task)
    {
        $wasCompleted = $task->status === 'completed';

        if (! $this->canUseApiPermission($request->user(), 'tasks-edit')) {
            if (! $this->isOwnTask($request->user(), $task)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            $validated = $this->validateOwnTaskUpdateData($request, $task);
            $task->update($validated);
            $freshTask = $task->fresh(['project', 'employee']);

            if (! $wasCompleted && $freshTask->status === 'completed') {
                $this->createNextRecurringTaskIfNeeded($freshTask);
            }

            return response()->json([
                'message' => 'Task updated successfully.',
                'task' => $this->taskPayload($freshTask),
            ]);
        }

        $validated = $this->validateTaskData($request, $task);

        if ($wasCompleted && ($validated['status'] ?? $task->status) === 'completed') {
            unset($validated['completed_at']);
        }

        $task->update($validated);
        $freshTask = $task->fresh(['project', 'employee']);

        if (! $wasCompleted && $freshTask->status === 'completed') {
            $this->createNextRecurringTaskIfNeeded($freshTask);
        }

        return response()->json([
            'message' => 'Task updated successfully.',
            'task' => $this->taskPayload($freshTask),
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
        $validated = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'employee_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'exists:users,id'],
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

        if ($this->canViewAllAppData($request->user())) {
            if (! blank($validated['user_id'] ?? null)) {
                $query->where('user_id', (int) $validated['user_id']);
            } elseif (! blank($validated['employee_id'] ?? null)) {
                $walletUserId = $this->userIdFromEmployeeId((int) $validated['employee_id']);
                $query->where('user_id', $walletUserId ?: 0);
            }
        } else {
            $query->where('user_id', $request->user()->id);
        }

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

        $creditTotal = (clone $query)->where('transfer_type', 0)->sum('amount');
        $debitTotal = (clone $query)->where('transfer_type', 1)->sum('amount');
        $netTotal = $creditTotal - $debitTotal;
        $wallets = $query
            ->latest('current_date')
            ->paginate((int) ($validated['per_page'] ?? 10));

        $wallets->setCollection($wallets->getCollection()->map(fn(Wallet $wallet) => $this->walletPayload($wallet)));

        return response()->json([
            'total_amount' => (int) $netTotal,
            'credit_total' => (int) $creditTotal,
            'debit_total' => (int) $debitTotal,
            'net_total' => (int) $netTotal,
            ...$wallets->toArray(),
        ]);
    }

    public function walletOptions(Request $request)
    {
        $user = $request->user();
        $canViewTransfers = $this->canUseApiPermission($user, 'transfers-list');
        $canCreateTransfers = $this->canUseApiPermission($user, 'transfers-create');

        return response()->json([
            'clients' => $this->scopeClientsForAppUser(Client::query(), $user)
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name']),
            'projects' => $this->scopeProjectsForAppUser(Project::query(), $user)
                ->whereIn('status', ['planning', 'active', 'on_hold'])
                ->orderBy('name')
                ->get(['id', 'client_id', 'name', 'status']),
            'employees' => User::query()
                ->when(! $this->canViewAllAppData($user), fn($query) => $query->whereKey($user->id))
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'wallet']),
            'payment_modes' => self::PAYMENT_MODES,
            'stages' => PaymentStage::query()
                ->orderBy('stage_name')
                ->get(['id', 'stage_name']),
            'wallet_balance' => (float) ($request->user()->wallet ?? 0),
            'can_view_transfers' => $canViewTransfers,
            'can_create_transfer' => $canCreateTransfers,
        ]);
    }

    public function transferWallet(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'transfers-create')) {
            return $forbidden;
        }

        $validated = $this->validateWalletData($request);
        $amount = (int) $validated['amount'];
        $actor = $request->user();
        $targetUser = $this->resolveWalletUser($validated, $request->user());
        $project = Project::query()->findOrFail((int) $validated['project_id']);

        if ((int) $project->client_id !== (int) $validated['client_id']) {
            throw ValidationException::withMessages([
                'project_id' => 'Selected project does not belong to the selected client.',
            ]);
        }

        if ((int) $validated['transfer_type'] === 1 && $amount > (float) ($targetUser->wallet ?? 0)) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is insufficient',
            ]);
        }

        if (
            (int) $validated['transfer_type'] === 0
            && (int) $targetUser->id !== (int) $actor->id
            && $amount > (float) ($actor->wallet ?? 0)
        ) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is insufficient',
            ]);
        }

        [$wallet, $counterWallet] = DB::transaction(function () use ($validated, $amount, $targetUser, $actor) {
            $dateTime = Carbon::parse($validated['current_date'] . ' ' . ($validated['time'] ?? now()->format('H:i')));
            $description = $validated['description'] ?? null;
            $transferType = (int) $validated['transfer_type'];

            $wallet = Wallet::query()->create([
                'user_id' => $targetUser->id,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'amount' => $amount,
                'payment_mode' => $validated['payment_mode'],
                'transfer_type' => $transferType,
                'stage_id' => $validated['stage_id'] ?? null,
                'description' => $description,
                'current_date' => $dateTime,
                'active_status' => 1,
                'delete_status' => 0,
            ]);

            $balanceService = app(CrmBalanceService::class);
            $counterWallet = null;

            if ($transferType === 0) {
                $balanceService->applyProjectIncome((int) $validated['project_id'], $amount);
                $balanceService->adjustUserWallet((int) $targetUser->id, $amount);

                if ((int) $targetUser->id !== (int) $actor->id) {
                    $balanceService->adjustUserWallet((int) $actor->id, -$amount);
                    $counterWallet = Wallet::query()->create([
                        'user_id' => $actor->id,
                        'client_id' => $validated['client_id'],
                        'project_id' => $validated['project_id'],
                        'amount' => $amount,
                        'payment_mode' => $validated['payment_mode'],
                        'transfer_type' => 1,
                        'stage_id' => $validated['stage_id'] ?? null,
                        'description' => $description,
                        'current_date' => $dateTime,
                        'active_status' => 1,
                        'delete_status' => 0,
                    ]);
                }

                return [$wallet, $counterWallet];
            }

            $balanceService->reverseProjectIncome((int) $validated['project_id'], $amount);
            $balanceService->adjustUserWallet((int) $targetUser->id, -$amount);

            if ((int) $targetUser->id !== (int) $actor->id) {
                $balanceService->adjustUserWallet((int) $actor->id, $amount);
                $counterWallet = Wallet::query()->create([
                    'user_id' => $actor->id,
                    'client_id' => $validated['client_id'],
                    'project_id' => $validated['project_id'],
                    'amount' => $amount,
                    'payment_mode' => $validated['payment_mode'],
                    'transfer_type' => 0,
                    'stage_id' => $validated['stage_id'] ?? null,
                    'description' => $description,
                    'current_date' => $dateTime,
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);
            }

            return [$wallet, $counterWallet];
        });

        return response()->json([
            'message' => 'Wallet entry saved successfully.',
            'wallet' => $this->walletPayload($wallet->load(['user', 'client', 'project', 'stage'])),
            'counter_wallet' => $counterWallet ? $this->walletPayload($counterWallet->load(['user', 'client', 'project', 'stage'])) : null,
            'wallet_balance' => (float) $targetUser->fresh()->wallet,
            'sender_wallet_balance' => (float) $actor->fresh()->wallet,
        ], 201);
    }
}
