<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->hasPermission('reports-list') || $user->hasPermission('expense-reports-list')),
            403
        );

        $type = $request->string('type')->toString();
        $type = in_array($type, ['site', 'office', 'total'], true) ? $type : 'site';

        $filters = $request->only(['date_from', 'date_to']);

        $data = match ($type) {
            'office' => $this->reportService->officeReport($filters),
            'total' => $this->reportService->totalReport($filters),
            default => $this->reportService->siteReport($filters),
        };

        return view('pages.reports.index', array_merge($data, ['type' => $type, 'filters' => $filters]));
    }
}
