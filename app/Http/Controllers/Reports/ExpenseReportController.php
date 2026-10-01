<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseReportController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        $projects = Project::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::query()->orderBy('name')->get(['id', 'name']);

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $expenseCategoryRaw = $isSqlite
            ? "TRIM(COALESCE(main_categories.name, '') || ' ' || COALESCE(categories.name, '')) as category"
            : "TRIM(CONCAT(COALESCE(main_categories.name, ''), ' ', COALESCE(categories.name, ''))) as category";
        $salaryCategoryRaw = $isSqlite
            ? "(COALESCE(employee_salaries.salary_type, '') || ' Salary') as category"
            : "CONCAT(COALESCE(employee_salaries.salary_type, ''), ' Salary') as category";

        $expenseQuery = DB::table('expenses')
            ->leftJoin('projects', 'projects.id', '=', 'expenses.project_id')
            ->leftJoin('users', 'users.id', '=', 'expenses.user_id')
            ->leftJoin('main_categories', 'main_categories.id', '=', 'expenses.main_category_id')
            ->leftJoin('categories', 'categories.id', '=', 'expenses.category_id')
            ->whereNull('expenses.deleted_at')
            ->select([
                DB::raw("'Expense' as source"),
                'expenses.id as id',
                DB::raw('COALESCE(expenses.current_date, expenses.created_at) as date'),
                DB::raw("COALESCE(projects.name, '-') as project"),
                DB::raw("COALESCE(users.name, '-') as employee"),
                DB::raw("COALESCE(expenses.description, 'Expense') as title"),
                DB::raw($expenseCategoryRaw),
                DB::raw("CASE WHEN COALESCE(expenses.unpaid_amt, 0) > 0 THEN 'pending' ELSE 'paid' END as status"),
                DB::raw('CAST(COALESCE(expenses.amount, 0) AS DECIMAL(14,2)) as amount'),
            ]);

        $salaryQuery = DB::table('employee_salaries')
            ->select([
                DB::raw("'Employee Salary' as source"),
                'employee_salaries.id as id',
                'employee_salaries.created_at as date',
                DB::raw("'-' as project"),
                DB::raw("COALESCE(employee_salaries.name, '-') as employee"),
                DB::raw("'Employee Salary' as title"),
                DB::raw($salaryCategoryRaw),
                DB::raw("'recorded' as status"),
                DB::raw('CAST(COALESCE(employee_salaries.salary, 0) AS DECIMAL(14,2)) as amount'),
            ]);

        $labourQuery = DB::table('labours')
            ->whereNull('labours.deleted_at')
            ->select([
                DB::raw("'Labour Salary' as source"),
                'labours.id as id',
                'labours.created_at as date',
                DB::raw("'-' as project"),
                DB::raw("COALESCE(labours.name, '-') as employee"),
                DB::raw("'Labour Salary' as title"),
                DB::raw("COALESCE(NULLIF(labours.job_title, ''), 'Labour') as category"),
                DB::raw("'recorded' as status"),
                DB::raw('CAST(COALESCE(labours.salary, 0) AS DECIMAL(14,2)) as amount'),
            ]);

        $includeSalary = true;
        $includeLabour = true;

        if ($request->filled('date_from')) {
            $from = $request->date('date_from')->toDateString();
            $expenseQuery->whereDate('expenses.current_date', '>=', $from);
            $salaryQuery->whereDate('employee_salaries.created_at', '>=', $from);
            $labourQuery->whereDate('labours.created_at', '>=', $from);
        }

        if ($request->filled('date_to')) {
            $to = $request->date('date_to')->toDateString();
            $expenseQuery->whereDate('expenses.current_date', '<=', $to);
            $salaryQuery->whereDate('employee_salaries.created_at', '<=', $to);
            $labourQuery->whereDate('labours.created_at', '<=', $to);
        }

        if ($request->filled('category')) {
            $term = '%' . trim($request->string('category')->toString()) . '%';
            $expenseQuery->where(function ($q) use ($term) {
                $q->where('main_categories.name', 'like', $term)
                  ->orWhere('categories.name', 'like', $term)
                  ->orWhere('expenses.description', 'like', $term);
            });
            $salaryQuery->where(function ($q) use ($term) {
                $q->where('employee_salaries.name', 'like', $term)
                  ->orWhere('employee_salaries.salary_type', 'like', $term);
            });
            $labourQuery->where(function ($q) use ($term) {
                $q->where('labours.name', 'like', $term)
                  ->orWhere('labours.job_title', 'like', $term);
            });
        }

        if ($request->filled('employee_id')) {
            $empId = $request->integer('employee_id');
            $expenseQuery->where('expenses.user_id', $empId);
            $salaryQuery->where('employee_salaries.user_id', $empId);
            $includeLabour = false;
        }

        if ($request->filled('project_id')) {
            $expenseQuery->where('expenses.project_id', $request->integer('project_id'));
            $includeSalary = false;
            $includeLabour = false;
        }

        if ($request->filled('status')) {
            $status = strtolower($request->string('status')->toString());
            if ($status === 'pending') {
                $expenseQuery->where('expenses.unpaid_amt', '>', 0);
                $includeSalary = false;
                $includeLabour = false;
            } elseif ($status === 'paid') {
                $expenseQuery->where('expenses.unpaid_amt', '<=', 0);
                $includeSalary = false;
                $includeLabour = false;
            } elseif (in_array($status, ['recorded', 'approved'], true)) {
                $expenseQuery->whereRaw('1 = 0');
            } else {
                $expenseQuery->whereRaw('1 = 0');
                $includeSalary = false;
                $includeLabour = false;
            }
        }

        $union = $expenseQuery;
        if ($includeSalary) {
            $union->unionAll($salaryQuery);
        }
        if ($includeLabour) {
            $union->unionAll($labourQuery);
        }

        $baseQuery = DB::query()->fromSub($union, 'report_rows');

        $totalCount = (int) (clone $baseQuery)->count();
        $totalAmount = (float) (clone $baseQuery)->sum('amount');

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = self::PER_PAGE;
        $dbRows = (clone $baseQuery)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $mapped = $dbRows->map(function ($row) {
            return [
                'source' => $row->source,
                'id' => $row->id,
                'date' => $row->date ? Carbon::parse($row->date) : null,
                'project' => $row->project ?: '-',
                'employee' => $row->employee ?: '-',
                'title' => $row->title ?: '-',
                'category' => $row->category ?: '-',
                'status' => $row->status,
                'amount' => (float) $row->amount,
            ];
        });

        $expenses = new LengthAwarePaginator(
            $mapped,
            $totalCount,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totals = [
            'count' => $totalCount,
            'amount' => $totalAmount,
        ];

        return view('pages.reports.expenses.index', compact('expenses', 'projects', 'employees', 'totals'));
    }
}
