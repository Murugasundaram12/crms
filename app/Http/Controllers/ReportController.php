<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    public function index(Request $request)
    {
        // Build the project list used in the reports page.
        $projectQuery = Project::with('client');
        $this->applyProjectSearchFilter($projectQuery, $request);

        $projects = $projectQuery->latest()->paginate(10)->withQueryString();

        // Load the summary metrics shown above the table.
        $summary = $this->reportService->projectSummary();

        return view('pages.projects.reports', compact('projects', 'summary'));
    }

    private function applyProjectSearchFilter($projectQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $projectQuery->where('name', 'like', "%{$searchTerm}%");
    }
}
