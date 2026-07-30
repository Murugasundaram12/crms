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

trait MobileExpensePaymentEndpoints
{
    public function expenseOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'expenses-list')) {
            return $forbidden;
        }

        return response()->json([
            'projects' => $this->scopeProjectsForAppUser(Project::query(), $request->user())->orderBy('name')->get(['id', 'name', 'client_id', 'status']),
            'main_categories' => MainCategory::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'main_category_id']),
            'payment_modes' => \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'payment_methods' => \App\Models\PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code', 'type']),
        ]);
    }

    public function expenses(Request $request)
    {
        $canListAll = $this->canViewAllAppData($request->user());

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'main_category_id' => ['nullable', 'exists:main_categories,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Expense::query()
            ->with(['project', 'user', 'mainCategory', 'category'])
            ->whereNull('labour_id')
            ->whereNull('vendor_id');

        if (! $canListAll) {
            $query->where('user_id', $request->user()->id);
        }

        $query
            ->when($validated['q'] ?? null, function ($q, string $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['project_id'] ?? null, fn($q, int $projectId) => $q->where('project_id', $projectId))
            ->when($validated['main_category_id'] ?? null, fn($q, int $mainCategoryId) => $q->where('main_category_id', $mainCategoryId))
            ->when($validated['category_id'] ?? null, fn($q, int $categoryId) => $q->where('category_id', $categoryId));

        if (! blank($validated['date_from'] ?? null)) {
            $query->whereDate('current_date', '>=', $request->date('date_from')->toDateString());
        }

        if (! blank($validated['date_to'] ?? null)) {
            $query->whereDate('current_date', '<=', $request->date('date_to')->toDateString());
        }

        $totalAmount = (clone $query)->sum('amount');
        $expenses = $query->latest('current_date')->paginate((int) ($validated['per_page'] ?? 15));
        $expenses->setCollection($expenses->getCollection()->map(fn(Expense $expense) => $this->employeeExpensePayload($expense)));

        return response()->json([
            'total_amount' => (float) $totalAmount,
            ...$expenses->toArray(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'expenses-create')) {
            return $forbidden;
        }

        $validated = $this->validateExpenseData($request);
        $expense = DB::transaction(function () use ($validated, $request) {
            $paidAmount = (int) $validated['paid_amt'];
            $userId = (int) $request->user()->id;

            $expense = Expense::query()->create($validated + [
                'user_id' => $userId,
                'unpaid_amt' => max((int) $validated['amount'] - $paidAmount, 0),
                'extra_amt' => max($paidAmount - (int) $validated['amount'], 0),
            ]);

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                null,
                0,
                $userId,
                $paidAmount,
                'Expense payment',
                'expense',
                (int) $expense->id
            );

            return $expense;
        });

        return response()->json([
            'message' => 'Expense created successfully.',
            'expense' => $this->employeeExpensePayload($expense->load(['project', 'mainCategory', 'category'])),
        ], 201);
    }

    public function showExpense(Request $request, Expense $expense)
    {
        if (! $this->canViewAllAppData($request->user()) && (int) $expense->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['expense' => $this->employeeExpensePayload($expense->load(['project', 'mainCategory', 'category']))]);
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'expenses-edit')) {
            return $forbidden;
        }

        $validated = $this->validateExpenseData($request);
        DB::transaction(function () use ($expense, $validated, $request) {
            $oldUserId = (int) $expense->user_id;
            $oldPaidAmount = (int) $expense->paid_amt;
            $newPaidAmount = (int) $validated['paid_amt'];

            $expense->update($validated + [
                'editedBy' => $request->user()->id,
                'unpaid_amt' => max((int) $validated['amount'] - $newPaidAmount, 0),
                'extra_amt' => max($newPaidAmount - (int) $validated['amount'], 0),
            ]);

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                $oldUserId,
                $oldPaidAmount,
                (int) $expense->user_id,
                $newPaidAmount,
                'Expense payment update',
                'expense',
                (int) $expense->id
            );
        });

        return response()->json([
            'message' => 'Expense updated successfully.',
            'expense' => $this->employeeExpensePayload($expense->fresh(['project', 'mainCategory', 'category'])),
        ]);
    }

    public function deleteExpense(Request $request, Expense $expense)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'expenses-delete')) {
            return $forbidden;
        }

        DB::transaction(function () use ($expense) {
            app(CrmBalanceService::class)->replaceUserWalletDebit(
                (int) $expense->user_id,
                (int) $expense->paid_amt,
                null,
                0,
                'Deleted expense refund',
                'expense',
                (int) $expense->id
            );

            $expense->delete();
        });

        return response()->json(['message' => 'Expense deleted successfully.']);
    }

    public function paymentOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'payments-list')) {
            return $forbidden;
        }

        $quotationColumns = ['id', 'client_id', 'project_id', 'quotation_number'];

        if (Schema::hasColumn('quotations', 'amount')) {
            $quotationColumns[] = 'amount';
        }

        if (Schema::hasColumn('quotations', 'total_amount')) {
            $quotationColumns[] = 'total_amount';
        }

        return response()->json([
            'clients' => $this->scopeClientsForAppUser(Client::query(), $request->user())->orderBy('name')->get(['id', 'name']),
            'projects' => $this->scopeProjectsForAppUser(Project::query(), $request->user())->orderBy('name')->get(['id', 'client_id', 'name']),
            'quotations' => Quotation::query()
                ->when(! $this->canViewAllAppData($request->user()), function ($query) use ($request) {
                    $projectIds = $this->ownedProjectIdsForUser($request->user());

                    $projectIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('project_id', $projectIds);
                })
                ->orderByDesc('id')
                ->get($quotationColumns),
            'stages' => PaymentStage::query()
                ->when(! $this->canViewAllAppData($request->user()), function ($query) use ($request) {
                    $projectIds = $this->ownedProjectIdsForUser($request->user());

                    $projectIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('project_id', $projectIds);
                })
                ->orderBy('stage_name')
                ->get(['id', 'stage_name']),
            'methods' => ['cash', 'bank_transfer'],
            'statuses' => ['pending', 'paid', 'overdue', 'partial'],
        ]);
    }

    public function payments(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'payments-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'overdue', 'partial'])],
            'client_id' => ['nullable', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = $this->scopePaymentsForAppUser(Payment::query(), $request->user())
            ->with(['client', 'project', 'quotation', 'stage'])
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['status'] ?? null, fn($query, string $status) => $query->where('status', $status))
            ->when($validated['client_id'] ?? null, fn($query, int $clientId) => $query->where('client_id', $clientId))
            ->when($validated['project_id'] ?? null, fn($query, int $projectId) => $query->where('project_id', $projectId))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 15));

        $payments->setCollection($payments->getCollection()->map(fn(Payment $payment) => $this->paymentPayload($payment)));

        return response()->json($payments);
    }

    public function showPayment(Request $request, Payment $payment)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'payments-list')) {
            return $forbidden;
        }

        if (! $this->canAccessPayment($request->user(), $payment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['payment' => $this->paymentPayload($payment->load(['client', 'project', 'quotation', 'stage']))]);
    }

    public function paymentStages(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'payment-stages-list')) {
            return $forbidden;
        }

        return response()->json([
            'data' => PaymentStage::query()->orderBy('stage_name')->get()->map(fn(PaymentStage $stage) => $this->paymentStagePayload($stage)),
        ]);
    }
}
