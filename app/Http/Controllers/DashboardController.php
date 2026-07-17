<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Load the summary cards shown at the top of the dashboard.
        $summary = $this->dashboardService->summary();
        $can = [
            'projects' => $user?->hasPermission('projects-list') ?? false,
            'clients' => $user?->hasPermission('clients-list') ?? false,
            'employees' => $user?->hasPermission('employees-list') ?? false,
            'tasks' => $user?->hasPermission('tasks-list') ?? false,
            'payments' => $user?->hasPermission('payments-list') ?? false,
            'expenses' => $user?->hasPermission('expenses-list') ?? false,
            'reports' => ($user?->hasPermission('reports-list') ?? false) || ($user?->hasPermission('expense-reports-list') ?? false),
        ];

        $summary = $this->maskSummaryByPermissions($summary, $can);

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->latest('check_in_at')
            ->first();

        // Load recent records for the dashboard widgets.
        $recentProjects = $can['projects'] ? $this->getRecentProjects() : collect();
        $recentTasks = $can['tasks'] ? $this->getRecentTasks() : collect();
        $recentPayments = $can['payments'] ? $this->getRecentPayments() : collect();

        return view('pages.dashboard.index', compact('summary', 'recentProjects', 'recentTasks', 'recentPayments', 'todayAttendance', 'can'));
    }

    private function maskSummaryByPermissions(array $summary, array $can): array
    {
        if (! $can['projects']) {
            $summary['projectCount'] = 0;
            $summary['totalBudget'] = 0;
            $summary['totalSpent'] = 0;
            $summary['budgetUtilization'] = 0;
        }

        if (! $can['clients']) {
            $summary['clientCount'] = 0;
        }

        if (! $can['employees']) {
            $summary['employeeCount'] = 0;
        }

        if (! $can['tasks']) {
            $summary['taskCount'] = 0;
            $summary['completedTasks'] = 0;
            $summary['pendingTasks'] = 0;
            $summary['completionRate'] = 0;
        }

        if (! $can['payments']) {
            $summary['totalPayments'] = 0;
        }

        if (! $can['expenses']) {
            $summary['expenseOnlyTotal'] = 0;
            $summary['employeeSalaryTotal'] = 0;
            $summary['labourSalaryTotal'] = 0;
            $summary['totalExpenses'] = 0;
        }

        if (! $can['payments'] || ! $can['expenses']) {
            $summary['netRevenue'] = 0;
        }

        return $summary;
    }

    private function getRecentProjects()
    {
        return Project::with(['client', 'manager'])
            ->withCount('tasks')
            ->latest()
            ->take(5)
            ->get();
    }

    private function getRecentTasks()
    {
        return Task::with(['project', 'employee'])
            ->latest()
            ->take(6)
            ->get();
    }

    private function getRecentPayments()
    {
        return Payment::with(['project', 'client'])
            ->latest()
            ->take(5)
            ->get();
    }
}
