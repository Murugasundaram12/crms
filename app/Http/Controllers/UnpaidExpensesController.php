<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseUnpaidDate;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\User;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnpaidExpensesController extends Controller
{
    public function history(Request $request)
    {
        $query = Expense::query()
            ->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category'])
            ->whereNull('labour_id')
            ->whereNull('vendor_id')
            ->where('unpaid_amt', '>', 0)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('description', 'like', "%{$q}%")
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($request->filled('main_category'), function ($query) use ($request) {
                $query->whereHas('mainCategory', fn($q) => $q->where('name', $request->string('main_category')->toString()));
            })
            ->when($request->filled('category_name'), function ($query) use ($request) {
                $query->whereHas('category', fn($q) => $q->where('name', $request->string('category_name')->toString()));
            })
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('member_id'), fn($q) => $q->where('user_id', $request->integer('member_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('current_date', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('current_date', '<=', $request->date('date_to')->toDateString()));

        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amt),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amt),0) as total_advanced_amount')
            ->first();

        $expenses = $query
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 10))
            ->withQueryString();

        $projects = Project::query()->orderBy('name')->get();
        $employees = User::query()->orderBy('name')->get();
        $mainCategories = MainCategory::query()->whereIn('status', ['active', 1])->orderBy('name')->pluck('name');
        $categories = Category::query()->orderBy('name')->pluck('name');
        $paymentModes = Expense::paymentModes();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees', 'totals', 'mainCategories', 'categories', 'paymentModes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'paid_amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expense = Expense::query()->whereNull('deleted_at')->findOrFail((int) $validated['expense_id']);
        $payAmount = (int) $validated['paid_amount'];

        DB::transaction(function () use ($expense, $payAmount, $validated) {
            $settlement = min($payAmount, (int) $expense->unpaid_amt);

            if ($settlement <= 0) {
                return;
            }

            ExpenseUnpaidDate::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'paid_amount' => $settlement,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'notes' => $validated['notes'] ?? null,
            ]);

            app(CrmBalanceService::class)->debitUserWallet((int) Auth::id(), $settlement, 'Unpaid expense settlement', 'expense_unpaid_settlement', (int) $expense->id);

            $expense->update([
                'paid_amt' => (int) $expense->paid_amt + $settlement,
                'unpaid_amt' => max((int) $expense->unpaid_amt - $settlement, 0),
                'editedBy' => Auth::id(),
            ]);
        });

        return redirect()->back()->with('success', 'Unpaid amount settled successfully.');
    }
}
