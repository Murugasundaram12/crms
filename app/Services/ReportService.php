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
use Illuminate\Support\Facades\Schema;
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

        $summary = $this->buildExpenseQuerySummary($query, $filters);

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

        if (! empty($filters['project_id'])) {
            $salaryQuery->whereRaw('1 = 0');
        }

        $salaryAmountColumn = Schema::hasColumn('employee_salaries', 'salary_amount') ? 'salary_amount' : 'salary';
        $salaryHasPaidColumn = Schema::hasColumn('employee_salaries', 'paid_amount');
        $salaryPaidColumn = $salaryHasPaidColumn ? 'paid_amount' : null;
        $salaryRemainingColumn = Schema::hasColumn('employee_salaries', 'remaining_amount') ? 'remaining_amount' : null;
        $salaryStatusColumn = Schema::hasColumn('employee_salaries', 'status') ? 'status' : null;
        $salaryPaidExpression = $salaryHasPaidColumn && $salaryStatusColumn
            ? "CASE WHEN employee_salaries.{$salaryStatusColumn} IN ('paid', 'partial') THEN COALESCE(employee_salaries.{$salaryPaidColumn}, 0) ELSE 0 END"
            : ($salaryHasPaidColumn ? "COALESCE(employee_salaries.{$salaryPaidColumn}, 0)" : '0');
        $salaryUnpaidExpression = $salaryRemainingColumn
            ? ($salaryStatusColumn
                ? "CASE WHEN employee_salaries.{$salaryStatusColumn} IN ('pending', 'partial') THEN COALESCE(employee_salaries.{$salaryRemainingColumn}, 0) ELSE 0 END"
                : "COALESCE(employee_salaries.{$salaryRemainingColumn}, 0)")
            : "GREATEST(COALESCE(employee_salaries.{$salaryAmountColumn}, 0) - "
                . ($salaryHasPaidColumn ? "COALESCE(employee_salaries.{$salaryPaidColumn}, 0)" : '0')
                . ', 0)';
        $salaryTotals = (clone $salaryQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("COALESCE(SUM(employee_salaries.{$salaryAmountColumn}), 0) as total_amount")
            ->selectRaw("COALESCE(SUM({$salaryPaidExpression}), 0) as paid_amount")
            ->selectRaw("COALESCE(SUM({$salaryUnpaidExpression}), 0) as remaining_amount")
            ->first();

        $labourSalaryQuery = DB::table('labour_salaries');
        $this->applyQueryDateFilters($labourSalaryQuery, $filters, 'payment_date');
        $labourTotals = (clone $labourSalaryQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(salary_amount), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('paid', 'partial') THEN paid_amount ELSE 0 END), 0) as paid_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('pending', 'partial') THEN remaining_amount ELSE 0 END), 0) as remaining_amount")
            ->first();

        $salaryAmount = (float) ($salaryTotals->total_amount ?? 0);
        $salaryPaidAmount = (float) ($salaryTotals->paid_amount ?? 0);
        $labourAmount = (float) ($labourTotals->total_amount ?? 0);
        $labourPaidAmount = (float) ($labourTotals->paid_amount ?? 0);
        $incomeQuery = Payment::query()->where('status', 'paid');
        $this->applyDateFilters($incomeQuery, $filters, 'payment_date');

        if (! empty($filters['project_id'])) {
            $incomeQuery->where('project_id', $filters['project_id']);
        }

        return [
            'count' => (int) ($expenseTotals->count ?? 0)
                + (int) ($salaryTotals->count ?? 0)
                + (int) ($labourTotals->count ?? 0),
            'total_amount' => (float) ($expenseTotals->total_amount ?? 0) + $salaryAmount + $labourAmount,
            'paid' => (float) ($expenseTotals->paid ?? 0) + $salaryPaidAmount + $labourPaidAmount,
            'unpaid' => (float) ($expenseTotals->unpaid ?? 0)
                + (float) ($salaryTotals->remaining_amount ?? 0)
                + (float) ($labourTotals->remaining_amount ?? 0),
            'income' => (float) $incomeQuery->sum('amount'),
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
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'expenses.payment_method_id')
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
                DB::raw("COALESCE(payment_methods.name, '-') as payment_mode"),
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

        $salaryAmountColumn = Schema::hasColumn('employee_salaries', 'salary_amount') ? 'salary_amount' : 'salary';
        $salaryHasPaidColumn = Schema::hasColumn('employee_salaries', 'paid_amount');
        $salaryPaidColumn = $salaryHasPaidColumn ? 'paid_amount' : null;
        $salaryRemainingColumn = Schema::hasColumn('employee_salaries', 'remaining_amount') ? 'remaining_amount' : null;
        $salaryStatusColumn = Schema::hasColumn('employee_salaries', 'status') ? 'status' : null;
        $salaryPaidExpression = $salaryHasPaidColumn && $salaryStatusColumn
            ? "CASE WHEN employee_salaries.{$salaryStatusColumn} IN ('paid', 'partial') THEN COALESCE(employee_salaries.{$salaryPaidColumn}, 0) ELSE 0 END"
            : ($salaryHasPaidColumn ? "COALESCE(employee_salaries.{$salaryPaidColumn}, 0)" : '0');
        $salaryUnpaidExpression = $salaryRemainingColumn
            ? ($salaryStatusColumn
                ? "CASE WHEN employee_salaries.{$salaryStatusColumn} IN ('pending', 'partial') THEN COALESCE(employee_salaries.{$salaryRemainingColumn}, 0) ELSE 0 END"
                : "COALESCE(employee_salaries.{$salaryRemainingColumn}, 0)")
            : "GREATEST(COALESCE(employee_salaries.{$salaryAmountColumn}, 0) - "
                . ($salaryHasPaidColumn ? "COALESCE(employee_salaries.{$salaryPaidColumn}, 0)" : '0')
                . ', 0)';

        $employeeSalaryQuery = DB::table('employee_salaries')
            ->select([
                DB::raw('DATE(employee_salaries.created_at) as date'),
                DB::raw("'-' as project_name"),
                DB::raw("'Salary' as main_category"),
                DB::raw("CONCAT(UPPER(LEFT(COALESCE(employee_salaries.salary_type, ''), 1)), SUBSTRING(COALESCE(employee_salaries.salary_type, ''), 2), ' Salary') as sub_category"),
                DB::raw("'-' as labour"),
                DB::raw("'-' as vendor"),
                DB::raw('NULL as income'),
                DB::raw("CAST(COALESCE(employee_salaries.{$salaryAmountColumn}, 0) AS DECIMAL(15,2)) as amount"),
                DB::raw("CAST({$salaryPaidExpression} AS DECIMAL(15,2)) as paid"),
                DB::raw("CAST({$salaryUnpaidExpression} AS DECIMAL(15,2)) as unpaid"),
                DB::raw("'Employee salary entry' as description"),
                DB::raw("'-' as payment_mode"),
                DB::raw("'System' as entry_name"),
                DB::raw("'System' as edit_name"),
                DB::raw('employee_salaries.created_at as sort_date'),
            ]);

        $this->applyQueryDateFilters($employeeSalaryQuery, $filters, 'employee_salaries.created_at');

        if (! empty($filters['project_id'])) {
            $employeeSalaryQuery->whereRaw('1 = 0');
        }

        $labourQuery = DB::table('labour_salaries')
            ->join('labours', 'labours.id', '=', 'labour_salaries.labour_id')
            ->select([
                DB::raw('DATE(labour_salaries.payment_date) as date'),
                DB::raw("'-' as project_name"),
                DB::raw("'Labour' as main_category"),
                DB::raw("COALESCE(NULLIF(labours.job_title, ''), 'Labour') as sub_category"),
                DB::raw('labours.name as labour'),
                DB::raw("'-' as vendor"),
                DB::raw('NULL as income'),
                DB::raw('CAST(COALESCE(labour_salaries.salary_amount, 0) AS DECIMAL(15,2)) as amount'),
                DB::raw("CAST(CASE WHEN labour_salaries.status IN ('paid', 'partial') THEN COALESCE(labour_salaries.paid_amount, 0) ELSE 0 END AS DECIMAL(15,2)) as paid"),
                DB::raw("CAST(CASE WHEN labour_salaries.status IN ('pending', 'partial') THEN COALESCE(labour_salaries.remaining_amount, 0) ELSE 0 END AS DECIMAL(15,2)) as unpaid"),
                DB::raw("'Labour salary entry' as description"),
                DB::raw("'-' as payment_mode"),
                DB::raw("'System' as entry_name"),
                DB::raw("'System' as edit_name"),
                DB::raw('labour_salaries.payment_date as sort_date'),
            ]);

        $this->applyQueryDateFilters($labourQuery, $filters, 'labour_salaries.payment_date');

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

    private function buildExpenseQuerySummary(Builder $query, array $filters): array
    {
        $totals = (clone $query)->reorder()
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt), 0) as paid')
            ->selectRaw('COALESCE(SUM(unpaid_amt), 0) as unpaid')
            ->toBase()
            ->first();

        $incomeQuery = Payment::query()->where('status', 'paid');
        $this->applyDateFilters($incomeQuery, $filters, 'payment_date');

        return [
            'count' => (int) ($totals->count ?? 0),
            'total_amount' => (float) ($totals->total_amount ?? 0),
            'paid' => (float) ($totals->paid ?? 0),
            'unpaid' => (float) ($totals->unpaid ?? 0),
            'income' => (float) $incomeQuery->sum('amount'),
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
