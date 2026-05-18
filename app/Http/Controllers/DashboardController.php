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
        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->latest('check_in_at')
            ->first();

        // Load recent records for the dashboard widgets.
        $recentProjects = $this->getRecentProjects();
        $recentTasks = $this->getRecentTasks();
        $recentPayments = $this->getRecentPayments();

        return view('pages.dashboard.index', compact('summary', 'recentProjects', 'recentTasks', 'recentPayments', 'todayAttendance'));
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
