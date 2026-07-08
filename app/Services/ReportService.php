<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ReportService
{
    private const PER_PAGE = 10;

    public function siteReport(array $filters): array
    {
        $query = Expense::query()
            ->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category', 'labour', 'vendor'])
            ->whereNotNull('project_id')
            ->latest('current_date');

        $this->applyDateFilters($query, $filters, 'current_date');

        $records = $query->paginate(self::PER_PAGE)->withQueryString();

        $summary = $this->buildExpenseQuerySummary($query);

        return [
            'records' => $this->mapExpenseRows($records),
            'summary' => $summary,
            'type' => 'site',
            'filters' => $filters,
        ];
    }

    public function officeReport(array $filters): array
    {
        $merged = $this->buildMergedPaginator($filters, false);

        return [
            'records' => $merged['records'],
            'summary' => $merged['summary'],
            'type' => 'office',
            'filters' => $filters,
        ];
    }

    public function totalReport(array $filters): array
    {
        $merged = $this->buildMergedPaginator($filters, true);

        return [
            'records' => $merged['records'],
            'summary' => $merged['summary'],
            'type' => 'total',
            'filters' => $filters,
        ];
    }

    private function buildMergedPaginator(array $filters, bool $includeProjectExpenses): array
    {
        $reportQuery = DB::query()->fromSub(
            $this->mergedReportUnion($filters, $includeProjectExpenses),
            'report_rows'
        );

        $summary = $this->buildMergedSummary($filters, $includeProjectExpenses);

        $page = Paginator::resolveCurrentPage();
        $perPage = self::PER_PAGE;
        $rows = (clone $reportQuery)
            ->orderByDesc('sort_date')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn(object $row) => $this->databaseReportRowToArray($row));

        return [
            'records' => new Paginator(
                $rows,
                (int) ($summary['count'] ?? 0),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            ),
            'summary' => $summary,
        ];
    }

    private function buildMergedSummary(array $filters, bool $includeProjectExpenses): array
    {
        $expenseQuery = DB::table('expenses')
            ->whereNull('deleted_at');

        if (! $includeProjectExpenses) {
            $expenseQuery->whereNull('project_id');
        }

        $this->applyQueryDateFilters($expenseQuery, $filters, 'current_date');

        if (! empty($filters['project_id'])) {
            $expenseQuery->where('project_id', $filters['project_id']);
        }

        $expenseTotals = (clone $expenseQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt), 0) as paid')
            ->selectRaw('COALESCE(SUM(unpaid_amt), 0) as unpaid')
            ->first();

        $salaryQuery = DB::table('employee_salaries');
        $this->applyQueryDateFilters($salaryQuery, $filters, 'created_at');
        $salaryTotals = (clone $salaryQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(salary), 0) as total_amount')
            ->first();

        $labourQuery = DB::table('labours')
            ->whereNull('deleted_at');
        $this->applyQueryDateFilters($labourQuery, $filters, 'created_at');
        $labourTotals = (clone $labourQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(salary), 0) as total_amount')
            ->first();

        $salaryAmount = (float) ($salaryTotals->total_amount ?? 0);
        $labourAmount = (float) ($labourTotals->total_amount ?? 0);

        return [
            'count' => (int) ($expenseTotals->count ?? 0)
                + (int) ($salaryTotals->count ?? 0)
                + (int) ($labourTotals->count ?? 0),
            'total_amount' => (float) ($expenseTotals->total_amount ?? 0) + $salaryAmount + $labourAmount,
            'paid' => (float) ($expenseTotals->paid ?? 0) + $salaryAmount + $labourAmount,
            'unpaid' => (float) ($expenseTotals->unpaid ?? 0),
            'income' => (float) Payment::query()->where('status', 'paid')->sum('amount'),
        ];
    }

    private function mergedReportUnion(array $filters, bool $includeProjectExpenses): QueryBuilder
    {
        $expenseQuery = DB::table('expenses')
            ->leftJoin('projects', 'projects.id', '=', 'expenses.project_id')
            ->leftJoin('main_categories', 'main_categories.id', '=', 'expenses.main_category_id')
            ->leftJoin('categories', 'categories.id', '=', 'expenses.category_id')
            ->leftJoin('labours', 'labours.id', '=', 'expenses.labour_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'expenses.vendor_id')
            ->leftJoin('users as entry_users', 'entry_users.id', '=', 'expenses.user_id')
            ->leftJoin('users as edit_users', 'edit_users.id', '=', 'expenses.editedBy')
            ->whereNull('expenses.deleted_at')
            ->select([
                DB::raw('DATE(COALESCE(expenses.current_date, expenses.created_at)) as date'),
                DB::raw("COALESCE(projects.name, '-') as project_name"),
                DB::raw("COALESCE(main_categories.name, '-') as main_category"),
                DB::raw("COALESCE(categories.name, '-') as sub_category"),
                DB::raw("COALESCE(labours.name, '-') as labour"),
                DB::raw("COALESCE(vendors.name, '-') as vendor"),
                DB::raw('NULL as income'),
                DB::raw('CAST(COALESCE(expenses.amount, 0) AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(COALESCE(expenses.paid_amt, 0) AS DECIMAL(15,2)) as paid'),
                DB::raw('CAST(COALESCE(expenses.unpaid_amt, 0) AS DECIMAL(15,2)) as unpaid'),
                DB::raw("COALESCE(expenses.description, '-') as description"),
                DB::raw($this->paymentModeCaseSql('expenses.payment_mode') . ' as payment_mode'),
                DB::raw("COALESCE(entry_users.name, 'System') as entry_name"),
                DB::raw("COALESCE(edit_users.name, 'System') as edit_name"),
                DB::raw('COALESCE(expenses.current_date, expenses.created_at) as sort_date'),
            ]);

        if (! $includeProjectExpenses) {
            $expenseQuery->whereNull('expenses.project_id');
        }

        $this->applyQueryDateFilters($expenseQuery, $filters, 'expenses.current_date');

        if (! empty($filters['project_id'])) {
            $expenseQuery->where('expenses.project_id', $filters['project_id']);
        }

        $employeeSalaryQuery = DB::table('employee_salaries')
            ->select([
                DB::raw('DATE(employee_salaries.created_at) as date'),
                DB::raw("'-' as project_name"),
                DB::raw("'Salary' as main_category"),
                DB::raw("CONCAT(UPPER(LEFT(COALESCE(employee_salaries.salary_type, ''), 1)), SUBSTRING(COALESCE(employee_salaries.salary_type, ''), 2), ' Salary') as sub_category"),
                DB::raw("'-' as labour"),
                DB::raw("'-' as vendor"),
                DB::raw('NULL as income'),
                DB::raw('CAST(COALESCE(employee_salaries.salary, 0) AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(COALESCE(employee_salaries.salary, 0) AS DECIMAL(15,2)) as paid'),
                DB::raw('CAST(0 AS DECIMAL(15,2)) as unpaid'),
                DB::raw("'Employee salary entry' as description"),
                DB::raw("'-' as payment_mode"),
                DB::raw("'System' as entry_name"),
                DB::raw("'System' as edit_name"),
                DB::raw('employee_salaries.created_at as sort_date'),
            ]);

        $this->applyQueryDateFilters($employeeSalaryQuery, $filters, 'employee_salaries.created_at');

        $labourQuery = DB::table('labours')
            ->whereNull('labours.deleted_at')
            ->select([
                DB::raw('DATE(labours.created_at) as date'),
                DB::raw("'-' as project_name"),
                DB::raw("'Labour' as main_category"),
                DB::raw("COALESCE(NULLIF(labours.job_title, ''), 'Labour') as sub_category"),
                DB::raw('labours.name as labour'),
                DB::raw("'-' as vendor"),
                DB::raw('NULL as income'),
                DB::raw('CAST(COALESCE(labours.salary, 0) AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(COALESCE(labours.salary, 0) AS DECIMAL(15,2)) as paid'),
                DB::raw('CAST(0 AS DECIMAL(15,2)) as unpaid'),
                DB::raw("'Labour salary entry' as description"),
                DB::raw("'-' as payment_mode"),
                DB::raw("'System' as entry_name"),
                DB::raw("'System' as edit_name"),
                DB::raw('labours.created_at as sort_date'),
            ]);

        $this->applyQueryDateFilters($labourQuery, $filters, 'labours.created_at');

        return $expenseQuery
            ->unionAll($employeeSalaryQuery)
            ->unionAll($labourQuery);
    }

    private function applyQueryDateFilters(QueryBuilder $query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }
    }

    private function databaseReportRowToArray(object $row): array
    {
        return [
            'date' => $row->date ?? '-',
            'project_name' => $row->project_name ?? '-',
            'main_category' => $row->main_category ?? '-',
            'sub_category' => $row->sub_category ?? '-',
            'labour' => $row->labour ?? '-',
            'vendor' => $row->vendor ?? '-',
            'income' => $row->income,
            'amount' => (float) $row->amount,
            'paid' => (float) $row->paid,
            'unpaid' => (float) $row->unpaid,
            'description' => $row->description ?? '-',
            'payment_mode' => $row->payment_mode ?? '-',
            'entry_name' => $row->entry_name ?? 'System',
            'edit_name' => $row->edit_name ?? 'System',
        ];
    }

    private function paymentModeCaseSql(string $column): string
    {
        $cases = collect(Expense::paymentModes())
            ->map(fn(string $label, int $id) => "WHEN {$id} THEN '" . str_replace("'", "''", $label) . "'")
            ->implode(' ');

        return "CASE {$column} {$cases} ELSE '-' END";
    }

    private function applyDateFilters(Builder $query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
    }

    private function buildExpenseQuerySummary(Builder $query): array
    {
        $totalAmount = (float) (clone $query)->sum('amount');
        $paid = (float) (clone $query)->sum('paid_amt');
        $unpaid = (float) (clone $query)->sum('unpaid_amt');

        return [
            'count' => (clone $query)->count(),
            'total_amount' => $totalAmount,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'income' => (float) Payment::query()->where('status', 'paid')->sum('amount'),
        ];
    }

    private function mapExpenseRows(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $collection = $paginator->getCollection()->map(function (Expense $expense) {
            return [
                'date' => optional($expense->current_date)->format('Y-m-d') ?? optional($expense->created_at)->format('Y-m-d'),
                'project_name' => $expense->project?->name ?? '-',
                'main_category' => $expense->mainCategory?->name ?? '-',
                'sub_category' => $expense->category?->name ?? '-',
                'labour' => $expense->labour?->name ?? '-',
                'vendor' => $expense->vendor?->name ?? '-',
                'income' => null,
                'amount' => (float) $expense->amount,
                'paid' => (float) $expense->paid_amt,
                'unpaid' => (float) $expense->unpaid_amt,
                'description' => $expense->description ?? '-',
                'payment_mode' => $expense->payment_mode_label ?? '-',
                'entry_name' => $expense->employee?->name ?? 'System',
                'edit_name' => $expense->editedByUser?->name ?? 'System',
            ];
        });

        $paginator->setCollection($collection);

        return $paginator;
    }

    private function splitCategory(string $category): array
    {
        if ($category === '') {
            return ['-', '-'];
        }

        if (str_contains($category, '>')) {
            $parts = array_map('trim', explode('>', $category, 2));

            return [$parts[0] ?: '-', $parts[1] ?: '-'];
        }

        return [$category, '-'];
    }
}
