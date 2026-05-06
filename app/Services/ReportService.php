<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;

class ReportService
{
    public function projectSummary(): array
    {
        return [
            'statusCounts' => Project::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'totalPayments' => (float) Payment::where('status', 'paid')->sum('amount'),
            'totalExpenses' => (float) Expense::sum('amount'),
        ];
    }

    public function siteReport(array $filters): array
    {
        $query = Task::with(['project.client'])
            ->whereIn('type', ['daily', 'weekly'])
            ->latest();

        $this->applyReportFilters($query, $filters, 'tasks');

        $records = $query->paginate(20);

        return [
            'records' => $records,
            'summary' => [
                'total_tasks' => $records->total(),
                'total_site_cost' => Payment::whereHas('project', fn($q) => $q->whereHas('tasks'))->sum('amount'),
            ],
            'filters' => $filters,
            'type' => 'site',
        ];
    }

    public function officeReport(array $filters): array
    {
        $query = Expense::with(['project.client'])
            ->where('type', '!=', 'salary')
            ->latest();

        $this->applyReportFilters($query, $filters, 'expenses');

        $records = $query->paginate(20);

        return [
            'records' => $records,
            'summary' => [
                'total_expenses' => $records->total(),
                'total_office_cost' => Expense::where('type', '!=', 'salary')->sum('amount'),
            ],
            'filters' => $filters,
            'type' => 'office',
        ];
    }

    public function totalReport(array $filters): array
    {
        $summary = [
            'total_projects' => Project::count(),
            'total_payments' => (float) Payment::where('status', 'paid')->sum('amount'),
            'total_expenses' => (float) Expense::sum('amount'),
            'total_tasks' => Task::count(),
        ];

        $this->applyReportFilters($summary, $filters);

        return [
            'summary' => $summary,
            'filters' => $filters,
            'type' => 'total',
        ];
    }

    private function applyReportFilters($queryOrSummary, array $filters, string $table = ''): void
    {
        if (isset($filters['date_from'])) {
            $queryOrSummary->whereDate($table . '.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $queryOrSummary->whereDate($table . '.created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['project_id'])) {
            $queryOrSummary->where($table . '.project_id', $filters['project_id']);
        }
        if (isset($filters['client_id'])) {
            $queryOrSummary->whereHas('project.client', fn($q) => $q->where('id', $filters['client_id']));
        }
    }
}
