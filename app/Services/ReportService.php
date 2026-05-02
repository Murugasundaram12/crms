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
            'priorityCounts' => Project::query()
                ->selectRaw('priority, COUNT(*) as total')
                ->groupBy('priority')
                ->pluck('total', 'priority'),
            'tasksByStatus' => Task::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'totalBudget' => (float) Project::sum('budget'),
            'totalSpent' => (float) Project::sum('spent'),
            'totalPayments' => (float) Payment::where('status', 'paid')->sum('amount'),
            'totalExpenses' => (float) Expense::sum('amount'),
        ];
    }
}
