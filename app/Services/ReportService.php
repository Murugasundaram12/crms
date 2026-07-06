<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class ReportService
{
    public function siteReport(array $filters): array
    {
        $query = Expense::query()
            ->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category', 'labour', 'vendor'])
            ->whereNotNull('project_id')
            ->latest('current_date');

        $this->applyDateFilters($query, $filters, 'current_date');

        $records = $query->paginate(20)->withQueryString();

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
        $expenseQuery = Expense::query()->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category', 'labour', 'vendor'])->latest('current_date');

        if ($includeProjectExpenses) {
            // Keep all expenses in total report.
        } else {
            $expenseQuery->whereNull('project_id');
        }

        $this->applyDateFilters($expenseQuery, $filters, 'current_date');

        $expenseRows = $expenseQuery->get()->map(function (Expense $expense) {
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
                '_sort_date' => optional($expense->current_date)?->format('Y-m-d H:i:s') ?? optional($expense->created_at)?->format('Y-m-d H:i:s'),
            ];
        });

        $employeeSalaryQuery = EmployeeSalary::query()->latest('created_at');
        if (! empty($filters['date_from'])) {
            $employeeSalaryQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $employeeSalaryQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $employeeSalaryRows = $employeeSalaryQuery->get()->map(function (EmployeeSalary $salary) {
            return [
                'date' => optional($salary->created_at)->format('Y-m-d'),
                'project_name' => '-',
                'main_category' => 'Salary',
                'sub_category' => ucfirst((string) $salary->salary_type) . ' Salary',
                'labour' => '-',
                'vendor' => '-',
                'income' => null,
                'amount' => (float) $salary->salary,
                'paid' => (float) $salary->salary,
                'unpaid' => 0.0,
                'description' => 'Employee salary entry',
                'payment_mode' => '-',
                'entry_name' => 'System',
                'edit_name' => 'System',
                '_sort_date' => optional($salary->created_at)?->format('Y-m-d H:i:s'),
            ];
        });

        $labourQuery = Labour::query()->latest('created_at');
        if (! empty($filters['date_from'])) {
            $labourQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $labourQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $labourRows = $labourQuery->get()->map(function (Labour $labour) {
            return [
                'date' => optional($labour->created_at)->format('Y-m-d'),
                'project_name' => '-',
                'main_category' => 'Labour',
                'sub_category' => $labour->job_title ?: 'Labour',
                'labour' => $labour->name,
                'vendor' => '-',
                'income' => null,
                'amount' => (float) $labour->salary,
                'paid' => (float) $labour->salary,
                'unpaid' => 0.0,
                'description' => 'Labour salary entry',
                'payment_mode' => '-',
                'entry_name' => 'System',
                'edit_name' => 'System',
                '_sort_date' => optional($labour->created_at)?->format('Y-m-d H:i:s'),
            ];
        });

        $merged = $expenseRows->concat($employeeSalaryRows)->concat($labourRows)
            ->sortByDesc('_sort_date')
            ->values();

        $summary = [
            'count' => $merged->count(),
            'total_amount' => (float) $merged->sum('amount'),
            'paid' => (float) $merged->sum('paid'),
            'unpaid' => (float) $merged->sum('unpaid'),
            'income' => (float) Payment::query()->where('status', 'paid')->sum('amount'),
        ];

        $page = Paginator::resolveCurrentPage();
        $perPage = 20;
        $paged = $merged->slice(($page - 1) * $perPage, $perPage)->values()->map(function (array $row) {
            unset($row['_sort_date']);
            return $row;
        });

        return [
            'records' => new Paginator(
                $paged,
                $merged->count(),
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
