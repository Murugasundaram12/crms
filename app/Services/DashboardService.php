<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function summary(): array
    {
        $projectCount = Project::count();
        $taskCount = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $totalBudget = Schema::hasColumn('projects', 'budget')
            ? (float) Project::sum('budget')
            : (float) Project::with('quotations')->get()->sum(fn(Project $project) => $project->budget);
        $totalSpent = Schema::hasColumn('projects', 'spent')
            ? (float) Project::sum('spent')
            : (float) Project::with('expenses')->get()->sum(fn(Project $project) => $project->spent);
        $paidRevenue = Schema::hasTable('payments')
            ? (float) Payment::where('status', 'paid')->sum('amount')
            : 0.0;
        $expenseTotal = $this->sumIfTableExists(Expense::class, 'amount');
        $employeeSalaryTotal = $this->sumIfTableExists(EmployeeSalary::class, 'salary');
        $labourSalaryTotal = $this->sumIfTableExists(Labour::class, 'salary');
        $totalExpenses = $expenseTotal + $employeeSalaryTotal + $labourSalaryTotal;
        $netRevenue = $paidRevenue - $totalExpenses;

        return [
            'projectCount' => $projectCount,
            'clientCount' => Client::count(),
            'employeeCount' => Employee::count(),
            'taskCount' => $taskCount,
            'completedTasks' => $completedTasks,
            'pendingTasks' => Task::where('status', '!=', 'completed')->count(),
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalPayments' => $paidRevenue,
            'expenseOnlyTotal' => $expenseTotal,
            'employeeSalaryTotal' => $employeeSalaryTotal,
            'labourSalaryTotal' => $labourSalaryTotal,
            'totalExpenses' => $totalExpenses,
            'netRevenue' => $netRevenue,
            'completionRate' => $taskCount > 0 ? round(($completedTasks / $taskCount) * 100, 1) : 0,
            'budgetUtilization' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0,
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function sumIfTableExists(string $modelClass, string $column): float
    {
        $model = new $modelClass();

        if (! Schema::hasTable($model->getTable()) || ! Schema::hasColumn($model->getTable(), $column)) {
            return 0.0;
        }

        return (float) $modelClass::sum($column);
    }
}
