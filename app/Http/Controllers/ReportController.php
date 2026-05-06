<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $type = $request->get('type', 'site');
        $filters = $request->only(['date_from', 'date_to', 'project_id', 'client_id']);

        $data = match ($type) {
            'site' => $this->reportService->siteReport($filters),
            'office' => $this->reportService->officeReport($filters),
            'total' => $this->reportService->totalReport($filters),
            default => $this->reportService->siteReport($filters),
        };

        return view('pages.reports.index', array_merge($data, ['type' => $type, 'filters' => $filters]));
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
