<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourSalaryAdvanceToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Labour $labour;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->user = User::factory()->create([
            'email' => 'adv_toggle_' . uniqid() . '@example.com',
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $this->user->role = 'Super Admin';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'wallet')) {
            $this->user->wallet = 50000.00;
        }
        $this->user->save();

        foreach (['labour-salaries-list', 'labour-salaries-create', 'labour-salaries-edit', 'labour-salaries-delete'] as $perm) {
            Permission::firstOrCreate(['key' => $perm], ['name' => $perm]);
        }

        $this->actingAs($this->user);

        $role = LabourRole::firstOrCreate(
            ['name' => 'Mason Test ' . uniqid()],
            ['salary_type' => 'daily', 'salary' => 600.00]
        );

        $phone = '98765' . rand(10000, 99999);
        $this->labour = Labour::create([
            'name' => 'Toggle Labour ' . uniqid(),
            'job_title' => 'Mason',
            'phone' => $phone,
            'phone_number' => $phone,
            'labour_role_id' => $role->id,
            'salary' => 600.00,
            'advance_amt' => 2000.00,
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            ['code' => 'CASH', 'active_status' => 1]
        );
    }

    /**
     * 1-4. Advance Paid = OFF:
     * - Advance Adjustment must be 0.
     * - Net Payable = Calculated Salary.
     * - Employee wallet debited only net salary (4800).
     * - Labour advance remains unchanged (2000).
     * - No AdvanceHistory settlement created.
     */
    public function test_advance_off_does_not_deduct_advance_and_debits_full_salary(): void
    {
        $initialWallet = (float) $this->user->wallet;
        $initialAdvance = (float) $this->labour->advance_amt;

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 4800.00,
            'advance_paid' => '0',
            'advance_adjusted' => '0.00',
            'paid_amount' => 4800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $this->labour->refresh();
        $this->user->refresh();

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);
        $this->assertEquals(0.00, (float) $salary->advance_adjusted);
        $this->assertEquals(4800.00, (float) $salary->paid_amount);

        // Advance balance unchanged
        $this->assertEquals($initialAdvance, (float) $this->labour->advance_amt);

        // Payer wallet debited full salary 4800
        $this->assertEquals($initialWallet - 4800.00, (float) $this->user->wallet);

        // No AdvanceHistory settlement created
        $this->assertEquals(0, AdvanceHistory::where('labour_salary_id', $salary->id)->count());

        // Expense record created for wallet payment
        $expense = Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(4800.00, (float) $expense->amount);
        $this->assertEquals(4800.00, (float) $expense->paid_amt);
    }

    /**
     * Exact Bug Test:
     * Calculated Salary = 4800, Advance Balance = 2000, Advance Paid = OFF.
     * Even if advance_adjusted was somehow sent as 0 or dirty value,
     * payment must succeed without "4,800 exceeds 2,000 advance" validation error.
     */
    public function test_exact_bug_advance_off_with_dirty_value_is_manipulation_proof(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 4800.00,
            'advance_paid' => '0',
            'advance_adjusted' => '4800.00', // Dirty/tampered value when OFF
            'paid_amount' => 4800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('labour-salaries.index'));

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);
        $this->assertEquals(0.00, (float) $salary->advance_adjusted);
    }

    /**
     * 5-8. Advance Paid = ON:
     * - Full valid advance adjustment: 2000 adjusted against 4800 salary.
     * - Net Payable = 2800.
     * - Employee wallet debited only net payable 2800.
     * - Labour advance reduced from 2000 to 0.
     * - AdvanceHistory settlement created for 2000.
     */
    public function test_advance_on_adjusts_advance_reduces_net_payable_and_creates_settlement(): void
    {
        $initialWallet = (float) $this->user->wallet;

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 4800.00,
            'advance_paid' => '1',
            'advance_adjusted' => '2000.00',
            'paid_amount' => 2800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $this->labour->refresh();
        $this->user->refresh();

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);
        $this->assertEquals(2000.00, (float) $salary->advance_adjusted);
        $this->assertEquals(2800.00, (float) $salary->paid_amount);

        // Labour advance reduced to 0
        $this->assertEquals(0.00, (float) $this->labour->advance_amt);

        // Wallet debited ONLY net payable 2800 (not 4800, not double debiting 2000)
        $this->assertEquals($initialWallet - 2800.00, (float) $this->user->wallet);

        // AdvanceHistory settlement created for 2000
        $settlement = AdvanceHistory::where('labour_salary_id', $salary->id)->first();
        $this->assertNotNull($settlement);
        $this->assertEquals(2000.00, (float) $settlement->amount);
        $this->assertEquals('settle', $settlement->entry_type);

        // Expense record created for only the actual wallet debit 2800 (no double counting)
        $expense = Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(2800.00, (float) $expense->amount);
        $this->assertEquals(2800.00, (float) $expense->paid_amt);
        $this->assertEquals(1, $expense->is_advance);
    }

    /**
     * 9. Adjustment cannot exceed available advance.
     */
    public function test_adjustment_cannot_exceed_available_advance(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 5000.00,
            'advance_paid' => '1',
            'advance_adjusted' => 2500.00, // Available advance is 2000
            'paid_amount' => 2500.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasErrors(['advance_adjusted']);
    }

    /**
     * 10. Adjustment cannot exceed calculated salary.
     */
    public function test_adjustment_cannot_exceed_calculated_salary(): void
    {
        $this->labour->update(['advance_amt' => 3000.00]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 1000.00,
            'advance_paid' => '1',
            'advance_adjusted' => 1500.00, // Exceeds salary of 1000
            'paid_amount' => 0.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasErrors(['advance_adjusted']);
    }

    /**
     * 11. Advance balance greater than salary:
     * Calculated Salary = 1500, Advance Balance = 2000, Advance Paid = ON.
     * Advance Adjustment = 1500, Net Payable = 0, Remaining Advance = 500.
     */
    public function test_advance_balance_greater_than_salary(): void
    {
        $initialWallet = (float) $this->user->wallet;

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 1500.00,
            'advance_paid' => '1',
            'advance_adjusted' => 1500.00,
            'paid_amount' => 0.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $this->labour->refresh();
        $this->user->refresh();

        // Remaining advance is 500
        $this->assertEquals(500.00, (float) $this->labour->advance_amt);

        // Wallet was NOT debited because paid_amount was 0
        $this->assertEquals($initialWallet, (float) $this->user->wallet);

        // AdvanceHistory settlement created for 1500
        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);
        $this->assertEquals(1500.00, (float) $salary->advance_adjusted);
        $this->assertEquals(0.00, (float) $salary->paid_amount);

        // Since paid_amount is 0, no cash outflow expense is created
        $this->assertEquals(0, Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->count());
    }

    /**
     * 12. Advance balance less than salary:
     * Calculated Salary = 4800, Advance Balance = 1000, Advance Paid = ON.
     * Advance Adjustment = 1000, Net Payable = 3800, Remaining Advance = 0.
     */
    public function test_advance_balance_less_than_salary(): void
    {
        $this->labour->update(['advance_amt' => 1000.00]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 4800.00,
            'advance_paid' => '1',
            'advance_adjusted' => 1000.00,
            'paid_amount' => 3800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $this->labour->refresh();
        $this->assertEquals(0.00, (float) $this->labour->advance_amt);

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertEquals(1000.00, (float) $salary->advance_adjusted);
        $this->assertEquals(3800.00, (float) $salary->paid_amount);
    }

    /**
     * 13. No advance balance:
     * Advance Balance = 0, Advance Paid = ON, Adjustment must be 0.
     */
    public function test_no_advance_balance_forces_zero_adjustment(): void
    {
        $this->labour->update(['advance_amt' => 0.00]);

        // Attempting adjustment > 0 must fail
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 2000.00,
            'advance_paid' => '1',
            'advance_adjusted' => 100.00,
            'paid_amount' => 1900.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $response->assertSessionHasErrors(['advance_adjusted']);

        // Adjustment 0 succeeds
        $pass = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 2000.00,
            'advance_paid' => '1',
            'advance_adjusted' => 0.00,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $pass->assertRedirect(route('labour-salaries.index'));
    }

    /**
     * 14. Payment method remains mandatory.
     */
    public function test_payment_method_remains_mandatory(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 2000.00,
            'advance_paid' => '0',
            'advance_adjusted' => 0.00,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => '',
        ]);

        $response->assertSessionHasErrors(['payment_method_id']);
    }

    /**
     * Delete Salary reverts advance balance and deletes Expense / Wallet records.
     */
    public function test_salary_deletion_reverts_advance_and_removes_expense(): void
    {
        $initialAdvance = (float) $this->labour->advance_amt;

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 3000.00,
            'advance_paid' => '1',
            'advance_adjusted' => 1000.00,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->labour->refresh();
        $this->assertEquals($initialAdvance - 1000.00, (float) $this->labour->advance_amt);

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);

        // Delete salary
        $delResponse = $this->delete(route('labour-salaries.destroy', $salary));
        $delResponse->assertRedirect(route('labour-salaries.index'));

        $this->labour->refresh();
        // Advance balance restored
        $this->assertEquals($initialAdvance, (float) $this->labour->advance_amt);

        // AdvanceHistory, Wallet, and Expense records deleted
        $this->assertEquals(0, AdvanceHistory::where('labour_salary_id', $salary->id)->count());
        $this->assertEquals(0, Wallet::where('source_type', 'labour_salary')->where('source_id', $salary->id)->count());
        $this->assertEquals(0, Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->count());
    }

    /**
     * Partial payment with Advance ON:
     * Calculated Salary = 4800, Advance = 2000, Net Payable = 2800.
     * Partial Paid Amount = 1500 -> Remaining = 1300, Status = 'partial'.
     */
    public function test_partial_salary_payment_with_advance_on(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 4800.00,
            'advance_paid' => '1',
            'advance_adjusted' => 2000.00,
            'paid_amount' => 1500.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary);
        $this->assertEquals(2000.00, (float) $salary->advance_adjusted);
        $this->assertEquals(1500.00, (float) $salary->paid_amount);
        $this->assertEquals(1300.00, (float) $salary->remaining_amount);
        $this->assertEquals('partial', $salary->status);

        // Expense reflects actual wallet payment of 1500
        $expense = Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(1500.00, (float) $expense->paid_amt);
    }

    /**
     * Updating salary from Advance OFF to Advance ON.
     */
    public function test_update_salary_from_advance_off_to_advance_on(): void
    {
        // 1. Create with Advance OFF
        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 4800.00,
            'advance_paid' => '0',
            'advance_adjusted' => 0.00,
            'paid_amount' => 4800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertEquals(0.00, (float) $salary->advance_adjusted);
        $this->assertEquals(4800.00, (float) $salary->paid_amount);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        $walletBeforeUpdate = (float) $this->user->fresh()->wallet;

        // 2. Update with Advance ON (adjust 2000, new paid amount 2800 -> 2000 refunded to wallet)
        $updateResponse = $this->put(route('labour-salaries.update', $salary), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 4800.00,
            'advance_paid' => '1',
            'advance_adjusted' => 2000.00,
            'paid_amount' => 2800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $updateResponse->assertRedirect(route('labour-salaries.index'));

        $salary->refresh();
        $this->assertEquals(2000.00, (float) $salary->advance_adjusted);
        $this->assertEquals(2800.00, (float) $salary->paid_amount);

        // Advance reduced to 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // Wallet credited 2000 difference (4800 - 2800 = 2000 refund)
        $this->assertEquals($walletBeforeUpdate + 2000.00, (float) $this->user->fresh()->wallet);

        // AdvanceHistory settlement created
        $this->assertEquals(1, AdvanceHistory::where('labour_salary_id', $salary->id)->count());

        // Expense updated to 2800
        $expense = Expense::where('source_type', 'labour_salary')->where('source_id', $salary->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(2800.00, (float) $expense->paid_amt);
    }

    /**
     * DashboardService does not double-count Labour Salary expenses.
     */
    public function test_dashboard_service_does_not_double_count_labour_salary(): void
    {
        $dashboardService = app(\App\Services\DashboardService::class);
        $summaryBefore = $dashboardService->summary();

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_amount' => 4800.00,
            'advance_paid' => '1',
            'advance_adjusted' => 2000.00,
            'paid_amount' => 2800.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $summaryAfter = $dashboardService->summary();

        // Labour salary total increased by exactly 2800
        $this->assertEquals($summaryBefore['labourSalaryTotal'] + 2800.00, $summaryAfter['labourSalaryTotal']);

        // expenseOnlyTotal did NOT double-count the labour salary expense
        $this->assertEquals($summaryBefore['expenseOnlyTotal'], $summaryAfter['expenseOnlyTotal']);

        // Total expenses increased by exactly 2800
        $this->assertEquals($summaryBefore['totalExpenses'] + 2800.00, $summaryAfter['totalExpenses']);
    }
}
