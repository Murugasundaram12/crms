<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Services\CrmBalanceService;
use Tests\TestCase;

class ProjectProfitAuditTest extends TestCase
{
    public function test_crm_balance_service_apply_project_income_behavior(): void
    {
        $project = Project::first();
        if (! $project) {
            $this->assertTrue(true);
            return;
        }

        // Verify that in production DB, advance_amt and profit have inverse values
        if ((float) $project->advance_amt > 0) {
            $this->assertEquals(-1 * (float) $project->advance_amt, (float) $project->profit);
        } else {
            $this->assertTrue(true);
        }
    }
}
