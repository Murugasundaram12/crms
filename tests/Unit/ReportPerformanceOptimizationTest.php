<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportPerformanceOptimizationTest extends TestCase
{
    public function test_report_service_summary_aggregation_is_accurate_and_preserves_decimals(): void
    {
        $reportService = new ReportService();

        $siteReport = $reportService->siteReport([]);
        $this->assertIsArray($siteReport['summary']);
        $this->assertArrayHasKey('count', $siteReport['summary']);
        $this->assertArrayHasKey('total_amount', $siteReport['summary']);
        $this->assertArrayHasKey('paid', $siteReport['summary']);
        $this->assertArrayHasKey('unpaid', $siteReport['summary']);

        // Verify count and totals match direct SQL calculation
        $expectedCount = DB::table('expenses')->whereNull('deleted_at')->whereNotNull('project_id')->count();
        $expectedTotal = (float) DB::table('expenses')->whereNull('deleted_at')->whereNotNull('project_id')->sum('amount');
        $expectedPaid = (float) DB::table('expenses')->whereNull('deleted_at')->whereNotNull('project_id')->sum('paid_amt');

        $this->assertEquals($expectedCount, $siteReport['summary']['count']);
        $this->assertEquals($expectedTotal, $siteReport['summary']['total_amount']);
        $this->assertEquals($expectedPaid, $siteReport['summary']['paid']);
    }
}
