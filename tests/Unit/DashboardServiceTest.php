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

    public function test_cash_basis_revenue_includes_both_paid_and_partial_payments(): void
    {
        $initialSummary = (new DashboardService())->summary();
        $initialRevenue = $initialSummary['totalPayments'];

        \App\Models\Payment::create([
            'amount' => 5000.00,
            'status' => 'paid',
        ]);

        \App\Models\Payment::create([
            'amount' => 3000.00,
            'status' => 'partial',
        ]);

        \App\Models\Payment::create([
            'amount' => 7000.00,
            'status' => 'pending',
        ]);

        $summary = (new DashboardService())->summary();

        // Only 'paid' and 'partial' should be recognized as cash revenue; 'pending' is excluded
        $this->assertEquals($initialRevenue + 8000.00, $summary['totalPayments']);
    }

    public function test_cash_basis_expenses_uses_paid_amt_not_accrual_amount(): void
    {
        $initialSummary = (new DashboardService())->summary();
        $initialExpenseOnly = $initialSummary['expenseOnlyTotal'];

        \App\Models\Expense::create([
            'amount' => 10000.00,
            'paid_amt' => 4000.00,
            'unpaid_amt' => 6000.00,
            'source_type' => null,
        ]);

        $summary = (new DashboardService())->summary();

        // In cash basis, cash disbursed is paid_amt (4000), not total invoice amount (10000)
        $this->assertEquals($initialExpenseOnly + 4000.00, $summary['expenseOnlyTotal']);
    }

    public function test_cash_basis_employee_salaries_uses_paid_amount_not_gross_salary(): void
    {
        $initialSummary = (new DashboardService())->summary();
        $initialEmployeeSalary = $initialSummary['employeeSalaryTotal'];

        \App\Models\EmployeeSalary::create([
            'salary' => 50000.00,
            'paid_amount' => 35000.00,
            'status' => 'partial',
        ]);

        $summary = (new DashboardService())->summary();

        // In cash basis, cash disbursed is paid_amount (35000), not gross entitlement (50000)
        $this->assertEquals($initialEmployeeSalary + 35000.00, $summary['employeeSalaryTotal']);
    }

    public function test_cash_basis_labour_advances_included_and_settlement_not_duplicated(): void
    {
        $initialSummary = (new DashboardService())->summary();
        $initialLabourSalary = $initialSummary['labourSalaryTotal'];

        $labour = Labour::create([
            'name' => 'Cash Basis Labour ' . uniqid(),
            'salary' => 600.00,
        ]);

        // 1. Advance paid out: 2000 cash disbursed
        \App\Models\AdvanceHistory::create([
            'labour_id' => $labour->id,
            'amount' => 2000.00,
            'entry_type' => 'credit',
        ]);

        // 2. Residual salary paid: 3000 cash disbursed (out of 5000 total earned)
        $salary = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_amount' => 5000.00,
            'advance_adjusted' => 2000.00,
            'paid_amount' => 3000.00,
            'status' => 'paid',
        ]);

        // 3. Advance settlement record: non-cash accounting adjustment
        \App\Models\AdvanceHistory::create([
            'labour_id' => $labour->id,
            'labour_salary_id' => $salary->id,
            'amount' => 2000.00,
            'entry_type' => 'settle',
        ]);

        $summary = (new DashboardService())->summary();

        // Total cash disbursed is 2000 (advance) + 3000 (residual salary) = 5000
        // Settle must NOT count as a duplicate 2000 disbursement!
        $this->assertEquals($initialLabourSalary + 5000.00, $summary['labourSalaryTotal']);
    }

    public function test_net_revenue_formula_matches_cash_received_minus_cash_disbursed(): void
    {
        $summary = (new DashboardService())->summary();

        $expectedExpenses = $summary['expenseOnlyTotal'] + $summary['employeeSalaryTotal'] + $summary['labourSalaryTotal'];
        $this->assertEquals($expectedExpenses, $summary['totalExpenses']);

        $expectedNetRevenue = $summary['totalPayments'] - $summary['totalExpenses'];
        $this->assertEquals($expectedNetRevenue, $summary['netRevenue']);
    }
}
