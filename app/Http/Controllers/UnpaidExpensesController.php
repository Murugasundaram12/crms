<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Employee;
use App\Models\Category;
use App\Models\ExpenseUnpaidDate;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnpaidExpensesController extends Controller
{
    public function history(Request $request)
    {
        $hasTypeColumn = Schema::hasColumn('expenses', 'type');
        $hasCategoryColumn = Schema::hasColumn('expenses', 'category');
        $hasExpenseDateColumn = Schema::hasColumn('expenses', 'expense_date');

        $query = Expense::query()
            ->where('delete_status', false)
            ->where('unpaid_amount', '>', 0)
            ->with(['project', 'employee'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($hasTypeColumn && $request->filled('main_category'), fn($q) => $q->where('type', $request->string('main_category')->toString()))
            ->when($hasCategoryColumn && $request->filled('category_name'), fn($q) => $q->where('category', $request->string('category_name')->toString()))
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('member_id'), fn($q) => $q->where('employee_id', $request->integer('member_id')))
            ->when($hasExpenseDateColumn && $request->filled('date_from'), fn($q) => $q->whereDate('expense_date', '>=', $request->date('date_from')->toDateString()))
            ->when($hasExpenseDateColumn && $request->filled('date_to'), fn($q) => $q->whereDate('expense_date', '<=', $request->date('date_to')->toDateString()));

        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amount),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amount),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amount),0) as total_advanced_amount')
            ->first();

        $expenses = $query
            ->latest()
            ->paginate((int) $request->get('paginate', 10))
            ->withQueryString();

        $projects = Project::query()->orderBy('name')->get();
        $employees = Employee::query()->orderBy('name')->get();
        $mainCategories = $hasTypeColumn
            ? MainCategory::query()->where('status', 'active')->orderBy('name')->pluck('name')
            : collect();
        $categories = $hasCategoryColumn
            ? Category::query()->orderBy('name')->pluck('name')
            : collect();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees', 'totals', 'mainCategories', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expense = Expense::query()->where('delete_status', false)->findOrFail((int) $validated['expense_id']);
        $payAmount = (float) $validated['paid_amount'];

        DB::transaction(function () use ($expense, $payAmount, $validated) {
            $currentUnpaid = (float) $expense->unpaid_amount;
            $settlement = min($payAmount, $currentUnpaid);
            $newUnpaid = max($currentUnpaid - $settlement, 0);

            ExpenseUnpaidDate::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'paid_amount' => $settlement,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'notes' => $validated['notes'] ?? null,
            ]);

            Wallet::create([
                'user_id' => Auth::id(),
                'amount' => $settlement,
                'transfer_type' => 1,
                'description' => 'Unpaid expense settlement',
                'reference_type' => 'expense_unpaid_settlement',
                'reference_id' => $expense->id,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);

            $expense->update([
                'paid_amount' => (float) $expense->paid_amount + $settlement,
                'unpaid_amount' => $newUnpaid,
                'status' => $newUnpaid > 0 ? 'pending' : 'paid',
            ]);
        });

        return redirect()->back()->with('success', 'Unpaid amount settled successfully.');
    }
}
