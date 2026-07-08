<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\MainCategory;
use App\Models\Project;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amount'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);

            $expense = Expense::create([
                'user_id' => Auth::id(),
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'labour_id' => $validated['labour_id'],
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
            ]);

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
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amount'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);
            $oldExtra = (int) $expense->extra_amt;
            $oldLabourId = (int) $expense->labour_id;
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
                'user_id' => Auth::id(),
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
                'editedBy' => Auth::id(),
            ]);
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

        return view('pages.labour_expenses.advance', compact(
            'history',
            'labours',
            'walletLabours',
            'unpaidExpenses',
            'totalWalletBalance',
            'totalUnpaidAmount'
        ));
    }

    public function advanceStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'entry_type' => ['required', 'in:credit,withdraw,settle'],
            'labour_expense_transaction_id' => ['nullable', 'required_if:entry_type,settle', 'exists:expenses,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);
            $amount = (int) $validated['amount'];

            if ($validated['entry_type'] === 'credit') {
                app(CrmBalanceService::class)->adjustLabourAdvance((int) $labour->id, $amount);

                AdvanceHistory::create([
                    'labour_id' => $labour->id,
                    'amount' => $amount,
                    'entry_type' => 'credit',
                    'notes' => $validated['notes'] ?? 'Labour wallet credited',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);

                return;
            }

            if ($validated['entry_type'] === 'withdraw') {
                $withdraw = min($amount, (int) $labour->advance_amt);

                if ($withdraw <= 0) {
                    return;
                }

                app(CrmBalanceService::class)->adjustLabourAdvance((int) $labour->id, -$withdraw);

                AdvanceHistory::create([
                    'labour_id' => $labour->id,
                    'amount' => $withdraw,
                    'entry_type' => 'withdraw',
                    'notes' => $validated['notes'] ?? 'Labour wallet withdrawn',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);

                return;
            }

            $expense = Expense::query()
                ->where('labour_id', $labour->id)
                ->where('unpaid_amt', '>', 0)
                ->lockForUpdate()
                ->findOrFail((int) $validated['labour_expense_transaction_id']);

            $settle = min($amount, (int) $labour->advance_amt, (int) $expense->unpaid_amt);

            if ($settle <= 0) {
                return;
            }

            app(CrmBalanceService::class)->adjustLabourAdvance((int) $labour->id, -$settle);
            $expense->update([
                'paid_amt' => (int) $expense->paid_amt + $settle,
                'unpaid_amt' => max((int) $expense->unpaid_amt - $settle, 0),
                'editedBy' => Auth::id(),
                'is_advance' => 1,
            ]);

            AdvanceHistory::create([
                'labour_id' => $labour->id,
                'labour_expense_transaction_id' => $expense->id,
                'amount' => $settle,
                'entry_type' => 'settle',
                'notes' => $validated['notes'] ?? 'Advance settled against unpaid labour expense',
                'user_id' => Auth::id(),
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);
        });

        return redirect()
            ->route('labour-expenses.advance-history', ['labour_id' => $validated['labour_id']])
            ->with('success', 'Labour wallet updated successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expenses,id'],
            'delete_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::query()->whereNotNull('labour_id')->findOrFail((int) $validated['id']);

            if ((int) $expense->extra_amt > 0) {
                app(CrmBalanceService::class)->adjustLabourAdvance((int) $expense->labour_id, -(int) $expense->extra_amt);
            }

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
            'paymentModes' => Expense::paymentModes(),
        ];
    }

    private function editingTransaction(Request $request): ?Expense
    {
        if (! $request->filled('edit')) {
            return null;
        }

        return Expense::query()
            ->with(['labour', 'project', 'mainCategory', 'category', 'user', 'editedByUser'])
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
            'amount' => ['required', 'integer', 'min:0'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'payment_mode' => ['nullable', 'integer'],
            'current_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string', 'max:250'],
        ]);
    }
}
