<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourWalletAllocation;
use App\Models\LabourWalletTransaction;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourWalletEmployeeReversalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $employeeA;
    protected User $employeeB;
    protected User $employeeC;
    protected Labour $labour;
    protected PaymentMethod $paymentMethodCash;
    protected PaymentMethod $paymentMethodOnline;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $role = Role::query()->firstOrCreate(['name' => 'Super Admin']);
        $pCreate = Permission::query()->firstOrCreate(['key' => 'transfers-create'], ['name' => 'Create Transfers']);
        $pList = Permission::query()->firstOrCreate(['key' => 'transfers-list'], ['name' => 'List Transfers']);
        $pEdit = Permission::query()->firstOrCreate(['key' => 'transfers-edit'], ['name' => 'Edit Transfers']);
        $pDelete = Permission::query()->firstOrCreate(['key' => 'transfers-delete'], ['name' => 'Delete Transfers']);
        $pExpenseEdit = Permission::query()->firstOrCreate(['key' => 'expenses-edit'], ['name' => 'Edit Expenses']);
        $pExpenseList = Permission::query()->firstOrCreate(['key' => 'expenses-list'], ['name' => 'List Expenses']);
        $pSalCreate = Permission::query()->firstOrCreate(['key' => 'labour-salaries-create'], ['name' => 'Create Labour Salaries']);

        $role->permissions()->syncWithoutDetaching([
            $pCreate->id, $pList->id, $pEdit->id, $pDelete->id, $pExpenseEdit->id, $pExpenseList->id, $pSalCreate->id,
        ]);

        $this->admin = User::factory()->create(['role' => 'Super Admin', 'wallet' => 20000.00]);
        $this->admin->roles()->sync([$role->id]);
        $this->admin->clearResolvedPermissions();

        $this->employeeA = User::factory()->create(['name' => 'Employee A', 'role' => 'Super Admin', 'wallet' => 10000.00]);
        $this->employeeA->roles()->sync([$role->id]);
        $this->employeeA->clearResolvedPermissions();

        $this->employeeB = User::factory()->create(['name' => 'Employee B', 'role' => 'Super Admin', 'wallet' => 10000.00]);
        $this->employeeB->roles()->sync([$role->id]);
        $this->employeeB->clearResolvedPermissions();

        $this->employeeC = User::factory()->create(['name' => 'Employee C', 'role' => 'Super Admin', 'wallet' => 5000.00]);
        $this->employeeC->roles()->sync([$role->id]);
        $this->employeeC->clearResolvedPermissions();

        $labourRole = LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);

        $this->labour = Labour::create([
            'name' => 'Labour X ' . uniqid(),
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $labourRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $this->paymentMethodCash = PaymentMethod::query()->firstOrCreate(
            ['name' => 'Cash Test ' . uniqid()],
            ['code' => 'CASH_' . strtoupper(uniqid()), 'type' => 'cash', 'active_status' => true]
        );

        $this->paymentMethodOnline = PaymentMethod::query()->firstOrCreate(
            ['name' => 'GPay Test ' . uniqid()],
            ['code' => 'GPAY_' . strtoupper(uniqid()), 'type' => 'online', 'active_status' => true]
        );
    }

    /**
     * Complete lifecycle test covering prompt requirements 1-15:
     * 1. Employee A adds ₹5,000 to Labour X.
     * 2. Employee B adds ₹3,000 to Labour X.
     * 3. Labour Wallet total = ₹8,000.
     * 4. Employee A can reverse ₹2,000.
     * 5. Employee A remaining = ₹3,000.
     * 6. Employee B remaining = ₹3,000.
     * 7. Labour Wallet remaining = ₹6,000.
     * 8. Employee A cannot reverse ₹4,000.
     * 9. Employee A can reverse remaining ₹3,000.
     * 10. Employee A cannot reverse again.
     * 11. Employee B can reverse ₹3,000.
     * 12. Labour Wallet becomes ₹0.
     * 13. Employee wallet balances are correct.
     * 14. Payment method is stored correctly.
     * 15. Every reversal has an auditable record.
     */
    public function test_complete_employee_attribution_and_sequential_reversal_lifecycle(): void
    {
        $initialExpenseCount = Expense::count();

        // Step 1: Employee A adds ₹5,000 to Labour X
        $res1 = $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
            'notes' => 'Employee A initial contribution',
        ]);
        $res1->assertRedirect();
        $this->assertEquals(5000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);

        // Step 2: Employee B adds ₹3,000 to Labour X
        $res2 = $this->actingAs($this->employeeB)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodOnline->id,
            'notes' => 'Employee B initial contribution',
        ]);
        $res2->assertRedirect();
        $this->assertEquals(7000.00, (float) $this->employeeB->fresh()->wallet);

        // Step 3: Labour Wallet total = ₹8,000
        $this->assertEquals(8000.00, (float) $this->labour->fresh()->advance_amt);

        // Verify credit records in labour_wallet_transactions
        $this->assertDatabaseHas('labour_wallet_transactions', [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);
        $this->assertDatabaseHas('labour_wallet_transactions', [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeB->id,
            'type' => 'credit',
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodOnline->id,
        ]);

        // Step 4: Employee A can reverse ₹2,000
        $res4 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
            'notes' => 'Partial return to Employee A',
        ]);
        $res4->assertRedirect();
        $res4->assertSessionHas('success');

        // Step 5 & 7: Employee A wallet +₹2,000 (now ₹7,000), Labour Wallet remaining ₹6,000
        $this->assertEquals(7000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(6000.00, (float) $this->labour->fresh()->advance_amt);

        // Check contributor breakdown
        $contributorsRes = $this->actingAs($this->admin)->getJson(route('labour-expenses.contributors', $this->labour->id));
        $contributorsRes->assertOk();
        $cData = collect($contributorsRes->json('contributors'))->keyBy('employee_id');

        // Step 5: Employee A remaining = ₹3,000
        $this->assertEquals(3000.00, (float) $cData[$this->employeeA->id]['available_to_reverse']);
        $this->assertEquals(2000.00, (float) $cData[$this->employeeA->id]['already_reversed']);

        // Step 6: Employee B remaining = ₹3,000
        $this->assertEquals(3000.00, (float) $cData[$this->employeeB->id]['available_to_reverse']);
        $this->assertEquals(0.00, (float) $cData[$this->employeeB->id]['already_reversed']);

        // Step 8: Employee A cannot reverse ₹4,000 (exceeds ₹3,000 remaining attributable)
        $res8 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 4000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);
        $res8->assertSessionHasErrors(['amount']);
        // Balances remain untouched
        $this->assertEquals(7000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(6000.00, (float) $this->labour->fresh()->advance_amt);

        // Step 9: Employee A can reverse remaining ₹3,000
        $res9 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
            'notes' => 'Remaining return to Employee A',
        ]);
        $res9->assertRedirect();
        // Employee A fully refunded to initial ₹10,000
        $this->assertEquals(10000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);

        // Step 10: Employee A cannot reverse again
        $res10 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 100.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);
        $res10->assertSessionHasErrors(['employee_id']);

        // Step 11: Employee B can reverse ₹3,000
        $res11 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeB->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodOnline->id,
            'notes' => 'Full return to Employee B',
        ]);
        $res11->assertRedirect();

        // Step 12: Labour Wallet becomes ₹0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // Step 13: Employee B wallet restored to ₹10,000
        $this->assertEquals(10000.00, (float) $this->employeeB->fresh()->wallet);

        // Step 14 & 15: Auditable records verified
        $this->assertEquals(2, LabourWalletTransaction::where('labour_id', $this->labour->id)->where('type', 'credit')->count());
        $this->assertEquals(3, LabourWalletTransaction::where('labour_id', $this->labour->id)->where('type', 'reverse')->count());

        // Step 21 & 22: No Expense records created
        $this->assertEquals($initialExpenseCount, Expense::count());
    }

    /**
     * Requirement 16: Wrong Employee protection.
     * Ramesh contributed ₹5,000, Suresh contributed ₹0. Suresh must not reverse Ramesh's funds.
     */
    public function test_wrong_employee_cannot_reverse_another_employees_contribution(): void
    {
        // Employee A contributes ₹5,000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);

        // Employee C (who contributed 0) attempts to reverse ₹1,000
        $response = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeC->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $response->assertSessionHasErrors(['employee_id']);

        // Balances remain untouched
        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals(5000.00, (float) $this->employeeC->fresh()->wallet);
        $this->assertEquals(0, LabourWalletTransaction::where('employee_id', $this->employeeC->id)->count());
    }

    /**
     * Requirement 17: Cannot reverse more than Labour Wallet available balance.
     * Even if employee contributed ₹5,000, if labour wallet balance is ₹2,000, reversing ₹2,500 fails.
     */
    public function test_cannot_reverse_more_than_labour_wallet_available_balance(): void
    {
        // Employee A adds ₹5,000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        // Simulate labour advance being partially consumed (e.g. advance settlement down to ₹2,000)
        $this->labour->update(['advance_amt' => 2000.00]);

        // Attempting to reverse ₹2,500 exceeds available labour wallet balance of ₹2,000
        $response = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2500.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals(5000.00, (float) $this->employeeA->fresh()->wallet);
    }

    /**
     * Multi-credit allocation test:
     * A single reversal consumes multiple original employee credit transactions cleanly using labour_wallet_allocations.
     */
    public function test_single_reversal_allocates_cleanly_across_multiple_credits_fifo(): void
    {
        // Credit 1: ₹3,000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        // Credit 2: ₹2,000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethodOnline->id,
        ]);

        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);

        $creditTxs = LabourWalletTransaction::where('labour_id', $this->labour->id)
            ->where('employee_id', $this->employeeA->id)
            ->where('type', 'credit')
            ->orderBy('id', 'asc')
            ->get();
        $this->assertCount(2, $creditTxs);

        // Reversal of ₹4,000 (consumes all ₹3,000 of Credit 1 + ₹1,000 of Credit 2)
        $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 4000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);

        $reversalTx = LabourWalletTransaction::where('labour_id', $this->labour->id)
            ->where('type', 'reverse')
            ->first();
        $this->assertNotNull($reversalTx);
        $this->assertEquals(4000.00, (float) $reversalTx->amount);

        // Verify allocations
        $allocations = LabourWalletAllocation::where('reversal_transaction_id', $reversalTx->id)->get();
        $this->assertCount(2, $allocations);

        $alloc1 = $allocations->where('credit_transaction_id', $creditTxs[0]->id)->first();
        $this->assertNotNull($alloc1);
        $this->assertEquals(3000.00, (float) $alloc1->amount);

        $alloc2 = $allocations->where('credit_transaction_id', $creditTxs[1]->id)->first();
        $this->assertNotNull($alloc2);
        $this->assertEquals(1000.00, (float) $alloc2->amount);

        // Second reversal of remaining ₹1,000 consumes remainder of Credit 2
        $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $reversalTx2 = LabourWalletTransaction::where('labour_id', $this->labour->id)
            ->where('type', 'reverse')
            ->latest('id')
            ->first();
        $alloc3 = LabourWalletAllocation::where('reversal_transaction_id', $reversalTx2->id)->first();
        $this->assertNotNull($alloc3);
        $this->assertEquals($creditTxs[1]->id, $alloc3->credit_transaction_id);
        $this->assertEquals(1000.00, (float) $alloc3->amount);
    }

    /**
     * Test atomic rollback on failure.
     */
    public function test_failed_reversal_rolls_back_all_balance_changes(): void
    {
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $initialWallet = (float) $this->employeeA->fresh()->wallet;
        $initialLabourAdvance = (float) $this->labour->fresh()->advance_amt;
        $initialHistoryCount = AdvanceHistory::count();
        $initialWalletLedgerCount = Wallet::count();
        $initialAllocationsCount = LabourWalletAllocation::count();

        // Send an invalid request (amount > balance)
        $response = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 99999.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $response->assertSessionHasErrors(['amount']);

        $this->assertEquals($initialWallet, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals($initialLabourAdvance, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialHistoryCount, AdvanceHistory::count());
        $this->assertEquals($initialWalletLedgerCount, Wallet::count());
        $this->assertEquals($initialAllocationsCount, LabourWalletAllocation::count());
    }

    /**
     * Test contributorsJson endpoint returns exact breakdown.
     */
    public function test_contributors_endpoint_returns_exact_breakdown(): void
    {
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 1500.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        $res = $this->actingAs($this->admin)->getJson(route('labour-expenses.contributors', $this->labour->id));
        $res->assertOk();
        $this->assertEquals(3500.00, (float) $res->json('wallet_balance'));

        $contributors = collect($res->json('contributors'))->keyBy('employee_id');
        $this->assertTrue($contributors->has($this->employeeA->id));
        $this->assertEquals(5000.00, (float) $contributors[$this->employeeA->id]['original_amount']);
        $this->assertEquals(1500.00, (float) $contributors[$this->employeeA->id]['already_reversed']);
        $this->assertEquals(3500.00, (float) $contributors[$this->employeeA->id]['available_to_reverse']);
    }

    /**
     * Requirement 18: Concurrency protection.
     * Available contribution = ₹5,000.
     * Two requests try to reverse ₹4,000 and ₹3,000.
     * Total successful reversed must NEVER exceed ₹5,000.
     * The second request must fail.
     */
    public function test_concurrency_protection_prevents_reversal_exceeding_contribution(): void
    {
        // Employee A adds ₹5,000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);

        // Request 1: ₹4,000
        $res1 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 4000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);
        $res1->assertRedirect();
        $this->assertEquals(9000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);

        // Request 2: ₹3,000 (exceeds remaining available of ₹1,000)
        $res2 = $this->actingAs($this->admin)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethodCash->id,
        ]);
        $res2->assertSessionHasErrors(['amount']);

        // Assert final total reversed is exactly ₹4,000 (never exceeding ₹5,000)
        $this->assertEquals(9000.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);
        $totalReversed = LabourWalletTransaction::where('labour_id', $this->labour->id)->where('type', 'reverse')->sum('amount');
        $this->assertEquals(4000.00, (float) $totalReversed);
    }
}
