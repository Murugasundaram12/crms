<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;

class DashboardService
{
    public function summary(): array
    {
        $projectCount = Project::count();
        $taskCount = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $totalBudget = (float) Project::sum('budget');
        $totalSpent = (float) Project::sum('spent');

        return [
            'projectCount' => $projectCount,
            'clientCount' => Client::count(),
            'employeeCount' => Employee::count(),
            'taskCount' => $taskCount,
            'completedTasks' => $completedTasks,
            'pendingTasks' => Task::where('status', '!=', 'completed')->count(),
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalPayments' => (float) Payment::where('status', 'paid')->sum('amount'),
            'totalExpenses' => (float) Expense::sum('amount'),
            'completionRate' => $taskCount > 0 ? round(($completedTasks / $taskCount) * 100, 1) : 0,
            'budgetUtilization' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0,
        ];
    }
}
