<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourWalletAllocation;
use App\Models\LabourWalletTransaction;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LabourExpensesController extends Controller
{
    public function history(Request $request)
    {
        $query = $this->labourExpenseQuery($request);
        [$transactions, $totals] = $this->paginateWithTotals($query, $request);
        $editingTransaction = $this->editingTransaction($request);

        return view('pages.labour_expenses.history', $this->viewData() + compact('transactions', 'totals', 'editingTransaction'));
    }

    public function weeklyHistory(Request $request)
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $transactions = Expense::query()
            ->whereNotNull('labour_id')
            ->whereBetween('current_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->with('labour')
            ->get();

        $labours = Labour::query()->orderBy('name')->get();

        return view('pages.labour_expenses.weekly', compact('transactions', 'labours', 'start', 'end'));
    }

    public function projectHistory(Request $request)
    {
        $transactions = Expense::query()
            ->whereNotNull('labour_id')
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->with(['labour', 'project'])
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        return response()->json($transactions);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($validated) {
            $amount = round((float) $validated['amount'], 2);
            $paidAmount = round((float) $validated['paid_amount'], 2);
            $unpaidAmount = round(max($amount - $paidAmount, 0), 2);
            $extraAmount = round(max($paidAmount - $amount, 0), 2);
            $userId = (int) Auth::id();

            $expense = Expense::create([
                'user_id' => $userId,
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'labour_id' => $validated['labour_id'],
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
            ]);

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                null,
                0,
                $userId,
                $paidAmount,
                'Labour expense payment',
                'labour_expense',
                (int) $expense->id
            );

            if ($extraAmount > 0) {
                app(CrmBalanceService::class)->adjustLabourAdvance((int) $validated['labour_id'], $extraAmount);
                AdvanceHistory::create([
                    'labour_id' => $validated['labour_id'],
                    'labour_expense_transaction_id' => $expense->id,
                    'amount' => $extraAmount,
                    'entry_type' => 'credit',
                    'notes' => 'Extra amount added as advance',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }
        });

        return redirect()
            ->route('labour-expenses.history', array_filter([
                'project_id' => $validated['project_id'] ?? null,
                'labour_id' => $validated['labour_id'] ?? null,
            ]))
            ->with('success', 'Labour expense stored successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $expense = Expense::query()
            ->whereNotNull('labour_id')
            ->whereNull('deleted_at')
            ->findOrFail($id);
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($expense, $validated) {
            $amount = round((float) $validated['amount'], 2);
            $paidAmount = round((float) $validated['paid_amount'], 2);
            $unpaidAmount = round(max($amount - $paidAmount, 0), 2);
            $extraAmount = round(max($paidAmount - $amount, 0), 2);
            $oldExtra = round((float) $expense->extra_amt, 2);
            $oldLabourId = (int) $expense->labour_id;
            $originalUserId = (int) $expense->user_id;
            $oldPaidAmount = round((float) $expense->paid_amt, 2);
            $newLabourId = (int) $validated['labour_id'];
            $balanceService = app(CrmBalanceService::class);

            if ($oldExtra > 0) {
                $balanceService->adjustLabourAdvance($oldLabourId, -$oldExtra);
            }
            if ($extraAmount > 0) {
                $balanceService->adjustLabourAdvance($newLabourId, $extraAmount);
            }

            $expense->update([
                'labour_id' => $newLabourId,
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
                'editedBy' => Auth::id(),
            ]);

            $balanceService->replaceUserWalletDebit(
                $originalUserId,
                $oldPaidAmount,
                $originalUserId,
                $paidAmount,
                'Labour expense payment update',
                'labour_expense',
                (int) $expense->id
            );
        });

        return redirect()
            ->route('labour-expenses.history', array_filter([
                'project_id' => $validated['project_id'] ?? null,
                'labour_id' => $validated['labour_id'] ?? null,
            ]))
            ->with('success', 'Labour expense updated successfully.');
    }

    public function advanceHistory(Request $request)
    {
        $history = AdvanceHistory::query()
            ->with(['labour', 'expense.project', 'user'])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->latest()
            ->get();

        $labours = Labour::query()->orderBy('name')->get();
        $selectedLabourId = $request->integer('labour_id') ?: null;
        $walletLabours = Labour::query()
            ->when($selectedLabourId, fn($query) => $query->where('id', $selectedLabourId))
            ->orderBy('name')
            ->get();
        $unpaidExpenses = Expense::query()
            ->whereNotNull('labour_id')
            ->where('unpaid_amt', '>', 0)
            ->when($selectedLabourId, fn($query) => $query->where('labour_id', $selectedLabourId))
            ->with(['labour', 'project'])
            ->latest('current_date')
            ->get();
        $totalWalletBalance = (float) $walletLabours->sum('advance_amt');
        $totalUnpaidAmount = (float) $unpaidExpenses->sum('unpaid_amt');
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('pages.labour_expenses.advance', compact(
            'history',
            'labours',
            'paymentMethods',
            'walletLabours',
            'unpaidExpenses',
            'totalWalletBalance',
            'totalUnpaidAmount'
        ));
    }

    public function advanceStore(Request $request): RedirectResponse
    {
        if ($request->input('entry_type') === 'reverse') {
            return $this->advanceReverse($request);
        }

        if ($request->input('entry_type') === 'withdraw') {
            throw ValidationException::withMessages([
                'entry_type' => 'Direct unattributed advance withdrawal is disabled. Please use the Reverse Amount feature with contributor attribution.',
            ]);
        }

        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'entry_type' => ['required', 'in:credit,settle,reverse'],
            'labour_expense_transaction_id' => ['nullable', 'required_if:entry_type,settle', 'exists:expenses,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => [
                'required_if:entry_type,credit',
                'nullable',
                Rule::exists('payment_methods', 'id')->where('active_status', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'payment_method_id' => 'Payment Method',
        ]);

        $userId = (int) Auth::id();

        DB::transaction(function () use ($validated, $userId) {
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);
            $amount = (float) $validated['amount'];
            $balanceService = app(CrmBalanceService::class);

            if ($validated['entry_type'] === 'credit') {
                // Check company/admin wallet balance
                $userWallet = (float) DB::table('users')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->value('wallet');

                if ($userWallet < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient company wallet balance. Available balance is Rs ' . number_format($userWallet, 2) . '.',
                    ]);
                }

                // Debit company wallet
                $balanceService->debitUserWallet($userId, $amount, 'Labour advance given to ' . $labour->name);

                // Increment labour advance balance
                $balanceService->adjustLabourAdvance((int) $labour->id, $amount);

                $advanceHistoryData = [
                    'labour_id' => $labour->id,
                    'amount' => $amount,
                    'entry_type' => 'credit',
                    'notes' => $validated['notes'] ?? 'Labour advance given',
                    'user_id' => $userId,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('advance_history', 'payment_method_id')) {
                    $advanceHistoryData['payment_method_id'] = (int) $validated['payment_method_id'];
                }

                $advanceHistory = AdvanceHistory::create($advanceHistoryData);

                \App\Models\Wallet::query()->create([
                    'user_id' => $userId,
                    'client_id' => 0,
                    'project_id' => 0,
                    'amount' => round($amount, 2),
                    'payment_mode' => (int) $validated['payment_method_id'],
                    'payment_method_id' => (int) $validated['payment_method_id'],
                    'transfer_type' => 1, // Debit
                    'source_type' => 'labour_advance',
                    'source_id' => $advanceHistory->id,
                    'description' => 'Labour advance given to ' . $labour->name,
                    'created_by' => $userId,
                    'current_date' => now(),
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);

                LabourWalletTransaction::query()->create([
                    'labour_id' => $labour->id,
                    'employee_id' => $userId,
                    'type' => 'credit',
                    'amount' => $amount,
                    'payment_method_id' => (int) $validated['payment_method_id'],
                    'notes' => $validated['notes'] ?? 'Labour advance given',
                    'created_by' => $userId,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);

                return;
            }

            // Settle against expense if requested
            $expense = Expense::query()
                ->where('labour_id', $labour->id)
                ->where('unpaid_amt', '>', 0)
                ->lockForUpdate()
                ->findOrFail((int) $validated['labour_expense_transaction_id']);

            $currentAdvance = (float) $labour->advance_amt;
            $unpaidAmt = (float) $expense->unpaid_amt;

            if ($amount > $currentAdvance) {
                throw ValidationException::withMessages([
                    'amount' => 'Settlement amount cannot exceed available labour advance balance of Rs ' . number_format($currentAdvance, 2) . '.',
                ]);
            }

            if ($amount > $unpaidAmt) {
                throw ValidationException::withMessages([
                    'amount' => 'Settlement amount cannot exceed unpaid expense amount of Rs ' . number_format($unpaidAmt, 2) . '.',
                ]);
            }

            $balanceService->adjustLabourAdvance((int) $labour->id, -$amount);
            $expense->update([
                'paid_amt' => round((float) $expense->paid_amt + $amount, 2),
                'unpaid_amt' => round(max((float) $expense->unpaid_amt - $amount, 0), 2),
                'editedBy' => $userId,
                'is_advance' => 1,
            ]);

            AdvanceHistory::create([
                'labour_id' => $labour->id,
                'labour_expense_transaction_id' => $expense->id,
                'amount' => $amount,
                'entry_type' => 'settle',
                'notes' => $validated['notes'] ?? 'Advance settled against unpaid labour expense',
                'user_id' => $userId,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);
        });

        return redirect()
            ->route('labour-expenses.advance-history', ['labour_id' => $validated['labour_id']])
            ->with('success', 'Labour advance transaction recorded successfully.');
    }

    public function advanceReverse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'employee_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where('active_status', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'payment_method_id' => 'Payment Method',
            'employee_id' => 'Contributor Employee',
            'labour_id' => 'Labour',
        ]);

        $labourId = (int) $validated['labour_id'];
        $employeeId = (int) $validated['employee_id'];
        $reverseAmount = round((float) $validated['amount'], 2);
        $paymentMethodId = (int) $validated['payment_method_id'];
        $notes = $validated['notes'] ?? null;
        $operatorId = (int) Auth::id();
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser instanceof User && $currentUser->isSuperAdmin();

        if (! $isSuperAdmin && $employeeId !== $operatorId) {
            throw ValidationException::withMessages([
                'employee_id' => 'You are only authorized to reverse your own contributions.',
            ]);
        }

        DB::transaction(function () use ($labourId, $employeeId, $reverseAmount, $paymentMethodId, $notes, $operatorId) {
            $labour = Labour::query()->lockForUpdate()->findOrFail($labourId);
            $employee = DB::table('users')->where('id', $employeeId)->lockForUpdate()->first();
            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Selected employee does not exist.',
                ]);
            }

            $currentLabourBalance = (float) $labour->advance_amt;
            if ($reverseAmount > $currentLabourBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'Reverse amount (Rs ' . number_format($reverseAmount, 2) . ') cannot exceed current Labour Wallet available balance of Rs ' . number_format($currentLabourBalance, 2) . '.',
                ]);
            }

            // Lock all credits of this employee for this labour
            $credits = LabourWalletTransaction::query()
                ->where('labour_id', $labourId)
                ->where('employee_id', $employeeId)
                ->where('type', 'credit')
                ->withSum('allocationsAsCredit as allocated_amount', 'amount')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            // Total credits
            $totalCredit = (float) $credits->sum('amount');
            // Total already reversed by this employee for this labour
            $alreadyReversed = (float) LabourWalletTransaction::query()
                ->where('labour_id', $labourId)
                ->where('employee_id', $employeeId)
                ->where('type', 'reverse')
                ->lockForUpdate()
                ->sum('amount');

            $availableToReverse = max(0.0, round($totalCredit - $alreadyReversed, 2));

            if ($availableToReverse <= 0.0) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Selected employee has no eligible remaining contribution to reverse for this labour.',
                ]);
            }

            if ($reverseAmount > $availableToReverse) {
                throw ValidationException::withMessages([
                    'amount' => 'Reverse amount (Rs ' . number_format($reverseAmount, 2) . ') exceeds remaining attributable contribution (Rs ' . number_format($availableToReverse, 2) . ') for this employee.',
                ]);
            }

            $balanceService = app(CrmBalanceService::class);

            // 1. Decrement Labour balance atomically
            $balanceService->adjustLabourAdvance($labourId, -$reverseAmount);

            // 2. Credit target employee wallet atomically (and sync employee table)
            $balanceService->creditUserWallet(
                $employeeId,
                $reverseAmount,
                'Labour Wallet reverse from ' . $labour->name,
                'labour_wallet_reverse',
                $labourId
            );

            // 3. Create reversal transaction
            $reversalTx = LabourWalletTransaction::create([
                'labour_id' => $labourId,
                'employee_id' => $employeeId,
                'type' => 'reverse',
                'amount' => $reverseAmount,
                'payment_method_id' => $paymentMethodId,
                'notes' => $notes ?: 'Labour Wallet reverse to ' . $employee->name,
                'created_by' => $operatorId,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);

            // 4. Allocate across credits FIFO (resolves multi-credit allocation cleanly)
            $toAllocate = $reverseAmount;
            foreach ($credits as $credit) {
                if ($toAllocate <= 0.0) {
                    break;
                }
                $allocated = (float) ($credit->allocated_amount ?? 0);
                $creditRemaining = max(0.0, round((float) $credit->amount - $allocated, 2));
                if ($creditRemaining <= 0.0) {
                    continue;
                }

                $allocAmount = min($toAllocate, $creditRemaining);
                LabourWalletAllocation::create([
                    'reversal_transaction_id' => $reversalTx->id,
                    'credit_transaction_id' => $credit->id,
                    'amount' => $allocAmount,
                ]);

                $toAllocate = round($toAllocate - $allocAmount, 2);
            }

            // 5. Create Wallet ledger row for employee
            $walletData = [
                'user_id' => $employeeId,
                'client_id' => 0,
                'project_id' => 0,
                'amount' => round($reverseAmount, 2),
                'payment_mode' => $paymentMethodId,
                'transfer_type' => 0, // 0 = Credit
                'description' => 'Labour Wallet reverse from ' . $labour->name,
                'created_by' => $operatorId,
                'current_date' => now(),
                'active_status' => 1,
                'delete_status' => 0,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('wallet', 'payment_method_id')) {
                $walletData['payment_method_id'] = $paymentMethodId;
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('wallet', 'source_type')) {
                $walletData['source_type'] = 'labour_wallet_reverse';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('wallet', 'source_id')) {
                $walletData['source_id'] = $reversalTx->id;
            }
            \App\Models\Wallet::query()->create($walletData);

            // 6. Create advance_history row for unified history display
            $advanceHistoryData = [
                'labour_id' => $labourId,
                'amount' => $reverseAmount,
                'entry_type' => 'withdraw',
                'notes' => $notes ?: 'Labour Wallet reverse to ' . $employee->name,
                'user_id' => $employeeId,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('advance_history', 'payment_method_id')) {
                $advanceHistoryData['payment_method_id'] = $paymentMethodId;
            }
            AdvanceHistory::create($advanceHistoryData);
        });

        return redirect()
            ->route('labour-expenses.advance-history', ['labour_id' => $labourId])
            ->with('success', 'Labour wallet amount reversed successfully to employee wallet.');
    }

    public function contributorsJson(Request $request, int $labourId)
    {
        $labour = Labour::findOrFail($labourId);

        $credits = LabourWalletTransaction::query()
            ->where('labour_id', $labourId)
            ->where('type', 'credit')
            ->selectRaw('employee_id, SUM(amount) as total_credit')
            ->groupBy('employee_id')
            ->pluck('total_credit', 'employee_id');

        $reversals = LabourWalletTransaction::query()
            ->where('labour_id', $labourId)
            ->where('type', 'reverse')
            ->selectRaw('employee_id, SUM(amount) as total_reversed')
            ->groupBy('employee_id')
            ->pluck('total_reversed', 'employee_id');

        $contributors = [];
        foreach ($credits as $empId => $totalCredit) {
            $reversed = (float) ($reversals[$empId] ?? 0.0);
            $available = max(0.0, round((float) $totalCredit - $reversed, 2));
            $user = User::find($empId);
            $contributors[] = [
                'employee_id' => (int) $empId,
                'employee_name' => $user?->name ?? ('Employee #' . $empId),
                'original_amount' => round((float) $totalCredit, 2),
                'already_reversed' => round($reversed, 2),
                'available_to_reverse' => $available,
                'effective_available' => min($available, (float) $labour->advance_amt),
            ];
        }

        return response()->json([
            'labour_id' => $labour->id,
            'labour_name' => $labour->name,
            'wallet_balance' => (float) $labour->advance_amt,
            'contributors' => $contributors,
        ]);
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expenses,id'],
            'delete_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::query()->whereNotNull('labour_id')->findOrFail((int) $validated['id']);

            if ((float) $expense->extra_amt > 0) {
                app(CrmBalanceService::class)->adjustLabourAdvance((int) $expense->labour_id, -round((float) $expense->extra_amt, 2));
            }

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                (int) $expense->user_id,
                round((float) $expense->paid_amt, 2),
                null,
                0,
                'Deleted labour expense refund',
                'labour_expense',
                (int) $expense->id
            );

            $expense->reason = $validated['delete_reason'];
            $expense->editedBy = Auth::id();
            $expense->save();
            $expense->delete();
        });

        return redirect()->back()->with('success', 'Labour expense deleted successfully.');
    }

    public function deletedHistory(Request $request)
    {
        $transactions = Expense::onlyTrashed()
            ->whereNotNull('labour_id')
            ->with(['labour', 'project', 'mainCategory', 'category', 'user', 'editedByUser'])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $labours = Labour::query()->orderBy('name')->get();

        return view('pages.labour_expenses.deleted', compact('transactions', 'labours'));
    }

    private function labourExpenseQuery(Request $request)
    {
        return Expense::query()
            ->whereNotNull('labour_id')
            ->with(['labour', 'project', 'mainCategory', 'category'])
            ->when($request->filled('main_category_id'), fn($q) => $q->where('main_category_id', $request->integer('main_category_id')))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('current_date', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('current_date', '<=', $request->date('date_to')->toDateString()))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('description', 'like', "%{$q}%")
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"));
                });
            });
    }

    private function paginateWithTotals($query, Request $request): array
    {
        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amt),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amt),0) as total_advanced_amount')
            ->first();

        $transactions = $query->latest('current_date')->paginate((int) $request->get('paginate', 10))->withQueryString();

        return [$transactions, $totals];
    }

    private function viewData(): array
    {
        return [
            'labours' => Labour::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'mainCategories' => MainCategory::query()->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function editingTransaction(Request $request): ?Expense
    {
        if (! $request->filled('edit')) {
            return null;
        }

        return Expense::query()
            ->with(['labour', 'project', 'mainCategory', 'category', 'user', 'editedByUser', 'paymentMethod'])
            ->whereNotNull('labour_id')
            ->whereNull('deleted_at')
            ->find($request->integer('edit'));
    }

    private function validateExpense(Request $request): array
    {
        $request->merge([
            'paid_amount' => $request->input('paid_amount', $request->input('paid_amt', 0)),
        ]);

        return $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'main_category_id' => ['nullable', 'exists:main_categories,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'current_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string', 'max:250'],
        ]);
    }
}
