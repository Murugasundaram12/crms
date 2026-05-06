<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenseQuery = Expense::with(['project', 'employee']);
        $this->applySearchFilter($expenseQuery, $request);
        $this->applyStatusFilter($expenseQuery, $request);

        $expenses = $expenseQuery->latest()->paginate(12)->withQueryString();

        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateExpenseData($request);

        Expense::create($validatedData);

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
        $validatedData = $this->validateExpenseData($request, $expense);

        $expense->update($validatedData);

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
                ->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('category', 'like', "%{$searchTerm}%")
                ->orWhereHas('project', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"))
                ->orWhereHas('employee', fn($q) => $q->where('name', 'like', "%{$searchTerm}%"));
        });
    }

    private function applyStatusFilter($expenseQuery, Request $request): void
    {
        $status = $request->string('status')->toString();

        if ($status === '') {
            return;
        }

        $expenseQuery->where('status', $status);
    }

    private function validateExpenseData(Request $request, ?Expense $expense = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['salary', 'material', 'travel', 'other'])],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'paid'])],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
