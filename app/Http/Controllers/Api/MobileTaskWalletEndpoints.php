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
        $canListAll = $this->canUseApiPermission($request->user(), 'tasks-list');
        $ownTaskEmployeeId = $canListAll ? null : $this->taskEmployeeIdFromUserId($request->user()->id);

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

        if (! $canListAll) {
            if (! $ownTaskEmployeeId) {
                $emptyTasks = Task::query()
                    ->whereRaw('1 = 0')
                    ->paginate((int) ($validated['per_page'] ?? 25));

                return response()->json($emptyTasks);
            }

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
                if (! $canListAll && $filter === 'employee_id') {
                    continue;
                }

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
        if (! $this->canUseApiPermission($request->user(), 'tasks-list') && ! $this->isOwnTask($request->user(), $task)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'task' => $this->taskPayload($task->load(['project', 'employee'])),
        ]);
    }

    public function updateTask(Request $request, Task $task)
    {
        if (! $this->canUseApiPermission($request->user(), 'tasks-edit')) {
            if (! $this->isOwnTask($request->user(), $task)) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            $validated = $this->validateOwnTaskUpdateData($request, $task);
            $task->update($validated);

            return response()->json([
                'message' => 'Task updated successfully.',
                'task' => $this->taskPayload($task->fresh(['project', 'employee'])),
            ]);
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
            ->when($validated['project_id'] ?? null, fn($q, $projectId) => $q->where('project_id', $projectId))
            ->when($validated['user_id'] ?? null, fn($q, $userId) => $q->where('user_id', $userId));

        if (! blank($validated['employee_id'] ?? null) && blank($validated['user_id'] ?? null)) {
            $walletUser = $this->resolveWalletUser($validated, $request->user());
            $query->where('user_id', $walletUser->id);
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
            'employees' => User::query()
                ->where('status', '!=', 'inactive')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'wallet']),
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
        $actor = $request->user();
        $targetUser = $this->resolveWalletUser($validated, $request->user());
        $project = Project::query()->findOrFail((int) $validated['project_id']);
        $isUserTransfer = (int) $targetUser->id !== (int) $actor->id;

        if ((int) $project->client_id !== (int) $validated['client_id']) {
            throw ValidationException::withMessages([
                'project_id' => 'Selected project does not belong to the selected client.',
            ]);
        }

        $sourceUser = $isUserTransfer && (int) $validated['transfer_type'] === 0 ? $actor : $targetUser;

        if ($amount > (float) ($sourceUser->wallet ?? 0)) {
            throw ValidationException::withMessages([
                'amount' => 'Amount is insufficient',
            ]);
        }

        $counterWallet = null;

        $wallet = DB::transaction(function () use ($validated, $amount, $actor, $targetUser, $isUserTransfer, &$counterWallet) {
            $dateTime = Carbon::parse($validated['current_date'] . ' ' . ($validated['time'] ?? now()->format('H:i')));
            $description = $validated['description'] ?? null;

            $wallet = Wallet::query()->create([
                'user_id' => $targetUser->id,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'amount' => $amount,
                'payment_mode' => $validated['payment_mode'],
                'transfer_type' => $validated['transfer_type'],
                'stage_id' => $validated['stage_id'] ?? null,
                'description' => $description,
                'current_date' => $dateTime,
                'active_status' => 1,
                'delete_status' => 0,
            ]);

            $balanceService = app(CrmBalanceService::class);

            if ($isUserTransfer) {
                if ((int) $validated['transfer_type'] === 0) {
                    $balanceService->adjustUserWallet((int) $actor->id, -$amount);
                    $balanceService->adjustUserWallet((int) $targetUser->id, $amount);

                    $counterWallet = Wallet::query()->create([
                        'user_id' => $actor->id,
                        'client_id' => $validated['client_id'],
                        'project_id' => $validated['project_id'],
                        'amount' => $amount,
                        'payment_mode' => $validated['payment_mode'],
                        'transfer_type' => 1,
                        'stage_id' => $validated['stage_id'] ?? null,
                        'description' => trim('Transfer to ' . $targetUser->name . ($description ? ': ' . $description : '')),
                        'current_date' => $dateTime,
                        'active_status' => 1,
                        'delete_status' => 0,
                    ]);

                    return $wallet;
                }

                $balanceService->adjustUserWallet((int) $targetUser->id, -$amount);
                $balanceService->adjustUserWallet((int) $actor->id, $amount);

                $counterWallet = Wallet::query()->create([
                    'user_id' => $actor->id,
                    'client_id' => $validated['client_id'],
                    'project_id' => $validated['project_id'],
                    'amount' => $amount,
                    'payment_mode' => $validated['payment_mode'],
                    'transfer_type' => 0,
                    'stage_id' => $validated['stage_id'] ?? null,
                    'description' => trim('Transfer from ' . $targetUser->name . ($description ? ': ' . $description : '')),
                    'current_date' => $dateTime,
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);

                return $wallet;
            }

            if ((int) $validated['transfer_type'] === 0) {
                $balanceService->applyProjectIncome((int) $validated['project_id'], $amount);
                $balanceService->adjustUserWallet((int) $targetUser->id, $amount);

                return $wallet;
            }

            $balanceService->reverseProjectIncome((int) $validated['project_id'], $amount);
            $balanceService->adjustUserWallet((int) $targetUser->id, -$amount);

            return $wallet;
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

