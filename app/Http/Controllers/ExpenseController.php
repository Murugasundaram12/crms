<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\MainCategory;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenseQuery = Expense::with(['project', 'employee', 'mainCategory', 'category'])
            ->whereNull('labour_id')
            ->whereNull('vendor_id');

        $this->applySearchFilter($expenseQuery, $request);

        $expenses = $expenseQuery->latest('current_date')->paginate(12)->withQueryString();

        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();
        $mainCategories = MainCategory::query()->where('status', 'active')->orderBy('name')->pluck('name');
        $categories = Category::query()->orderBy('name')->pluck('name');
        $totals = (clone $expenseQuery)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amt),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amt),0) as total_advanced_amount')
            ->first();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees', 'mainCategories', 'categories', 'totals'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateExpenseData($request);
        Expense::create($validatedData + [
            'user_id' => Auth::id(),
            'unpaid_amt' => max((int) $validatedData['amount'] - (int) $validatedData['paid_amt'], 0),
            'extra_amt' => max((int) $validatedData['paid_amt'] - (int) $validatedData['amount'], 0),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {
        return redirect()->route('expenses.index', ['highlight' => $expense->id]);
    }

    public function edit(Expense $expense)
    {
        return redirect()->route('expenses.index', ['edit' => $expense->id]);
    }

    public function update(Request $request, Expense $expense)
    {
        $validatedData = $this->validateExpenseData($request);
        $expense->update($validatedData + [
            'editedBy' => Auth::id(),
            'unpaid_amt' => max((int) $validatedData['amount'] - (int) $validatedData['paid_amt'], 0),
            'extra_amt' => max((int) $validatedData['paid_amt'] - (int) $validatedData['amount'], 0),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }

    private function applySearchFilter($expenseQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $expenseQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('description', 'like', "%{$searchTerm}%")
                ->orWhereHas('mainCategory', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                ->orWhereHas('project', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                ->orWhereHas('employee', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"));
        });
    }

    private function validateExpenseData(Request $request): array
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
}
