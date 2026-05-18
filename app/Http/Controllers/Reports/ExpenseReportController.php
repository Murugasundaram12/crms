<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExpenseReportController extends Controller
{
    public function index(Request $request)
    {
        $expenseQuery = Expense::query()
            ->with(['project', 'employee'])
            ->latest('expense_date')
            ->latest('id');

        if ($request->filled('project_id')) {
            $expenseQuery->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('employee_id')) {
            $expenseQuery->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $expenseQuery->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $expenseQuery->where('category', 'like', '%'.$request->string('category')->toString().'%');
        }

        if ($request->filled('date_from')) {
            $expenseQuery->whereDate('expense_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $expenseQuery->whereDate('expense_date', '<=', $request->date('date_to')->toDateString());
        }

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        $expenses = $expenseQuery->get()->map(function (Expense $expense) {
            return [
                'source' => 'Expense',
                'id' => $expense->id,
                'date' => optional($expense->expense_date)?->startOfDay() ?? $expense->created_at,
                'project' => $expense->project?->name ?? '-',
                'employee' => $expense->employee?->name ?? '-',
                'title' => $expense->title,
                'category' => $expense->category ?? '-',
                'status' => $expense->status ?? 'pending',
                'amount' => (float) $expense->amount,
            ];
        });

        $employeeSalaryQuery = EmployeeSalary::query()->latest('created_at');
        if ($request->filled('date_from')) {
            $employeeSalaryQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $employeeSalaryQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
        if ($request->filled('category')) {
            $term = $request->string('category')->toString();
            $employeeSalaryQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('salary_type', 'like', '%'.$term.'%');
            });
        }

        $employeeSalaries = $employeeSalaryQuery->get()->map(function (EmployeeSalary $salary) {
            return [
                'source' => 'Employee Salary',
                'id' => $salary->id,
                'date' => $salary->created_at,
                'project' => '-',
                'employee' => $salary->name,
                'title' => 'Employee Salary',
                'category' => ucfirst($salary->salary_type).' Salary',
                'status' => 'recorded',
                'amount' => (float) $salary->salary,
            ];
        });

        $labourSalaryQuery = Labour::query()->latest('created_at');
        if ($request->filled('date_from')) {
            $labourSalaryQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $labourSalaryQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
        if ($request->filled('category')) {
            $term = $request->string('category')->toString();
            $labourSalaryQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('job_title', 'like', '%'.$term.'%');
            });
        }

        $labourSalaries = $labourSalaryQuery->get()->map(function (Labour $labour) {
            return [
                'source' => 'Labour Salary',
                'id' => $labour->id,
                'date' => $labour->created_at,
                'project' => '-',
                'employee' => $labour->name,
                'title' => 'Labour Salary',
                'category' => $labour->job_title ?: 'Labour',
                'status' => 'recorded',
                'amount' => (float) $labour->salary,
            ];
        });

        $merged = $expenses->concat($employeeSalaries)->concat($labourSalaries);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $merged = $merged->filter(fn(array $row) => strtolower((string) $row['status']) === strtolower($status));
        }

        $merged = $merged->sortByDesc(function (array $row) {
            return optional($row['date'])->timestamp ?? 0;
        })->values();

        $expenses = $this->paginateCollection($merged, 15, $request);

        $totals = [
            'count' => $merged->count(),
            'amount' => (float) $merged->sum('amount'),
        ];

        return view('pages.reports.expenses.index', compact('expenses', 'projects', 'employees', 'totals'));
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
