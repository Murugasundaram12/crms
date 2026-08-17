<?php

namespace Tests\Unit;

use App\Models\Labour;
use App\Models\LabourSalary;
use App\Services\DashboardService;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    public function test_dashboard_summary_excludes_master_labour_profile_salary(): void
    {
        $dashboardService = new DashboardService();
        $summary = $dashboardService->summary();

        // Master labour directory profiles should NOT be summed into labourSalaryTotal
        $masterLabourSum = (float) Labour::sum('salary');
        $actualLabourSalaryPaid = (float) LabourSalary::sum('paid_amount');

        $this->assertEquals($actualLabourSalaryPaid, $summary['labourSalaryTotal']);
        if ($masterLabourSum > 0 && $actualLabourSalaryPaid === 0.0) {
            $this->assertNotEquals($masterLabourSum, $summary['labourSalaryTotal']);
        }
    }
}
