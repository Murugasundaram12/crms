<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\TransferDetails;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourWalletTransferTest extends TestCase
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

        $role = \App\Models\Role::query()->firstOrCreate(['name' => 'Super Admin']);
        $pCreate = Permission::query()->firstOrCreate(['key' => 'transfers-create'], ['name' => 'Create Transfers']);
        $pList = Permission::query()->firstOrCreate(['key' => 'transfers-list'], ['name' => 'List Transfers']);
        $pEdit = Permission::query()->firstOrCreate(['key' => 'transfers-edit'], ['name' => 'Edit Transfers']);
        $pDelete = Permission::query()->firstOrCreate(['key' => 'transfers-delete'], ['name' => 'Delete Transfers']);
        $pExpenseEdit = Permission::query()->firstOrCreate(['key' => 'expenses-edit'], ['name' => 'Edit Expenses']);
        $pSalCreate = Permission::query()->firstOrCreate(['key' => 'labour-salaries-create'], ['name' => 'Create Labour Salaries']);
        $pSalList = Permission::query()->firstOrCreate(['key' => 'labour-salaries-list'], ['name' => 'List Labour Salaries']);

        $role->permissions()->syncWithoutDetaching([
            $pCreate->id, $pList->id, $pEdit->id, $pDelete->id, $pExpenseEdit->id, $pSalCreate->id, $pSalList->id,
        ]);

        $this->user = User::factory()->create([
            'role' => 'Super Admin',
            'wallet' => 1500.00,
        ]);
        $this->user->roles()->sync([$role->id]);
        $this->user->clearResolvedPermissions();

        $labourRole = LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);

        $this->labour = Labour::create([
            'name' => 'John Labour',
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $labourRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $this->paymentMethod = PaymentMethod::query()->firstOrCreate(
            ['name' => 'Cash'],
            ['code' => 'CASH', 'type' => 'cash', 'is_active' => true]
        );
    }

    public function test_successful_employee_to_labour_transfer(): void
    {
        $response = $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
            'description' => 'Test labour transfer',
        ]);

        $response->assertRedirect(route('transfers.index'));
        $response->assertSessionHas('success');

        // Employee wallet debited
        $this->assertEquals(500.00, (float) $this->user->fresh()->wallet);

        // Labour advance_amt MUST NOT change for a normal transfer
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // transferdetails ledger row created
        $this->assertDatabaseHas('transferdetails', [
            'user_id' => $this->user->id,
            'labour_id' => $this->labour->id,
            'transfer_type' => 'labour',
            'amount' => 1000,
        ]);

        // AdvanceHistory MUST NOT be created as an advance credit
        $this->assertDatabaseMissing('advance_history', [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
        ]);
    }

    public function test_exact_wallet_balance_transfer_succeeds_and_leaves_zero_balance(): void
    {
        $response = $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertRedirect(route('transfers.index'));
        $this->assertEquals(0.00, (float) $this->user->fresh()->wallet);
        // Labour advance_amt remains unchanged
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
    }

    public function test_transfer_exceeding_wallet_balance_is_rejected(): void
    {
        $initialTransferCount = TransferDetails::where('user_id', $this->user->id)->count();

        $response = $this->from(route('transfers.create'))->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(1500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialTransferCount, TransferDetails::where('user_id', $this->user->id)->count());
        $this->assertDatabaseMissing('advance_history', ['labour_id' => $this->labour->id]);
    }

    public function test_failed_transfer_does_not_modify_balances(): void
    {
        $initialTransferCount = TransferDetails::where('user_id', $this->user->id)->count();

        $response = $this->from(route('transfers.create'))->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => 99999, // Invalid Labour ID
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertSessionHasErrors(['labour_id']);
        $this->assertEquals(1500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialTransferCount, TransferDetails::where('user_id', $this->user->id)->count());
    }

    public function test_transferdetails_row_created(): void
    {
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 300.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $transfer = TransferDetails::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('labour', $transfer->transfer_type);
        $this->assertEquals($this->labour->id, $transfer->labour_id);
        $this->assertNull($transfer->employee_id);
        $this->assertNull($transfer->vendor_id);
    }

    public function test_advance_history_not_created_during_normal_transfer(): void
    {
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 400.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
            'description' => 'Normal wallet transfer',
        ]);

        $history = AdvanceHistory::where('labour_id', $this->labour->id)->first();
        $this->assertNull($history);
    }

    public function test_expenses_row_not_created_during_transfer(): void
    {
        $initialExpenseCount = Expense::query()->count();

        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $this->assertEquals($initialExpenseCount, Expense::query()->count());
    }

    public function test_editing_labour_transfer_recalculates_balances_correctly(): void
    {
        $this->user->update(['wallet' => 5000.00]);

        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $transfer = TransferDetails::where('user_id', $this->user->id)->latest('id')->first();

        $this->assertEquals(4000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        $response = $this->actingAs($this->user)->put(route('transfers.update', $transfer->id), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertRedirect(route('transfers.index'));

        // Difference of ₹500 is debited from employee wallet
        $this->assertEquals(3500.00, (float) $this->user->fresh()->wallet);
        // Labour advance_amt remains 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
    }

    public function test_deleting_labour_transfer_refunds_employee_and_keeps_advance_unchanged(): void
    {
        $this->user->update(['wallet' => 4000.00]);
        $this->labour->update(['advance_amt' => 200.00]);

        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $transfer = TransferDetails::where('user_id', $this->user->id)->latest('id')->first();
        $this->assertEquals(3000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(200.00, (float) $this->labour->fresh()->advance_amt);

        $response = $this->actingAs($this->user)->delete(route('transfers.destroy', $transfer->id));
        $response->assertRedirect(route('transfers.index'));

        // Employee wallet refunded by ₹1,000
        $this->assertEquals(4000.00, (float) $this->user->fresh()->wallet);
        // Labour advance_amt is still ₹200 (unchanged)
        $this->assertEquals(200.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertTrue((bool) $transfer->fresh()->delete_status);
    }

    public function test_unauthorized_user_cannot_create_transfer(): void
    {
        $regularUser = User::factory()->create(['role' => 'Worker', 'wallet' => 5000.00]);

        $response = $this->actingAs($regularUser)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertStatus(302);
    }

    public function test_invalid_labour_rejected_by_validation(): void
    {
        $response = $this->from(route('transfers.create'))->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => 999999,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertSessionHasErrors(['labour_id']);
    }

    public function test_transfer_index_displays_labour_transfer(): void
    {
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 750.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response = $this->actingAs($this->user)->get(route('transfers.index', ['transfer_type' => 'labour']));
        $response->assertStatus(200);
        $response->assertSee('John Labour');
        $response->assertSee('750.00');
    }

    public function test_concurrency_protection_prevents_overdrawing_employee_wallet(): void
    {
        $user = User::factory()->create(['wallet' => 1500.00, 'role' => 'Super Admin']);
        $role = \App\Models\Role::query()->firstOrCreate(['name' => 'Super Admin']);
        $user->roles()->sync([$role->id]);
        $user->clearResolvedPermissions();

        $labourRole = LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);
        $labour = Labour::create(['name' => 'Concurrent Labour', 'phone' => '9876543211', 'phone_number' => '9876543211', 'labour_role_id' => $labourRole->id, 'salary' => 500.00, 'advance_amt' => 0.00]);

        // Request 1: 1000
        $this->actingAs($user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        // Request 2: 1000 (should fail as remaining wallet is 500)
        $response2 = $this->from(route('transfers.create'))->actingAs($user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response2->assertSessionHasErrors(['amount']);
        $this->assertEquals(500.00, (float) $user->fresh()->wallet);
        $this->assertEquals(0.00, (float) $labour->fresh()->advance_amt);
    }

    // ==========================================
    // EXPLICIT TEST CASES REQUIRED BY SPEC
    // ==========================================

    /**
     * CASE 1: Labour Advance
     * Employee wallet ₹10,000
     * Labour advance ₹0
     * Give advance ₹2,000
     *
     * Expected:
     * Employee wallet ₹8,000
     * Labour advance ₹2,000
     */
    public function test_case_1_direct_labour_advance_flow(): void
    {
        $this->user->update(['wallet' => 10000.00]);
        $this->labour->update(['advance_amt' => 0.00]);

        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Direct Labour Advance',
        ]);

        $response->assertRedirect();
        $this->assertEquals(8000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $this->labour->id,
            'amount' => 2000.00,
            'entry_type' => 'credit',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * CASE 2: Normal Labour Transfer
     * Employee wallet ₹8,000
     * Labour advance ₹2,000
     * Transfer ₹1,000 to Labour
     *
     * Expected:
     * Employee wallet ₹7,000
     * Labour advance STILL ₹2,000
     */
    public function test_case_2_normal_labour_transfer_does_not_change_advance_amt(): void
    {
        $this->user->update(['wallet' => 8000.00]);
        $this->labour->update(['advance_amt' => 2000.00]);

        $response = $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
            'description' => 'Normal Labour Transfer',
        ]);

        $response->assertRedirect(route('transfers.index'));
        $this->assertEquals(7000.00, (float) $this->user->fresh()->wallet);
        // Labour advance MUST STILL be ₹2,000
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // No new advance_history credit created
        $this->assertEquals(0, AdvanceHistory::where('labour_id', $this->labour->id)->count());
    }

    /**
     * CASE 3: Salary Later
     * Earned salary ₹3,000
     * Advance ₹2,000
     * Normal transfer ₹1,000
     *
     * The ₹1,000 normal transfer MUST NOT be treated as advance.
     * Salary advance adjustment must only consider the actual outstanding advance ₹2,000.
     */
    public function test_case_3_salary_advance_adjustment_only_considers_actual_advance(): void
    {
        $this->user->update(['wallet' => 10000.00]);
        $this->labour->update(['advance_amt' => 0.00, 'salary' => 3000.00]);

        // 1. Give direct advance ₹2,000
        $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // 2. Normal transfer ₹1,000 to labour
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);
        // Advance remains strictly ₹2,000 (normal transfer not treated as advance)
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // 3. Trying to adjust ₹2,500 advance during salary settlement should FAIL because available advance is only ₹2,000
        $failResponse = $this->actingAs($this->user)->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => now()->startOfMonth()->toDateString(),
            'salary_period_end' => now()->endOfMonth()->toDateString(),
            'salary_amount' => 3000.00,
            'advance_adjusted' => 2500.00,
            'paid_amount' => 500.00,
            'payment_date' => now()->toDateString(),
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $failResponse->assertSessionHasErrors(['advance_adjusted']);

        // 4. Settling with exactly ₹2,000 advance adjustment succeeds
        $passResponse = $this->actingAs($this->user)->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => now()->startOfMonth()->toDateString(),
            'salary_period_end' => now()->endOfMonth()->toDateString(),
            'salary_amount' => 3000.00,
            'advance_adjusted' => 2000.00,
            'paid_amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $passResponse->assertRedirect();
        // Advance balance is reduced to 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
    }

    /**
     * CASE 4: Existing employee/vendor transfers continue working.
     */
    public function test_case_4_existing_employee_and_vendor_transfers_continue_working(): void
    {
        $this->user->update(['wallet' => 5000.00]);

        $recipientUser = User::factory()->create(['wallet' => 100.00]);
        $employee = Employee::create([
            'id' => $recipientUser->id,
            'name' => 'Recipient Employee',
            'email' => $recipientUser->email,
            'phone_number' => '9876543212',
            'wallet' => 100.00,
        ]);

        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'phone_number' => '9876543213',
            'advance_amt' => 50.00,
        ]);

        // Employee transfer
        $respEmp = $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'employee',
            'employee_id' => $employee->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);
        $respEmp->assertRedirect(route('transfers.index'));
        $this->assertEquals(4500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(600.00, (float) $recipientUser->fresh()->wallet);

        // Vendor transfer
        $respVen = $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'vendor',
            'vendor_id' => $vendor->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);
        $respVen->assertRedirect(route('transfers.index'));
        $this->assertEquals(3500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(1050.00, (float) $vendor->fresh()->advance_amt);
    }

    /**
     * CASE 5: Existing direct Labour Advance continues working (including withdrawal).
     */
    public function test_case_5_existing_direct_labour_advance_with_withdrawal(): void
    {
        $this->user->update(['wallet' => 5000.00]);
        $this->labour->update(['advance_amt' => 0.00]);

        // 1. Give Advance ₹2,000
        $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Direct Advance',
        ]);
        $this->assertEquals(3000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // 2. Withdraw Advance ₹500
        $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'withdraw',
            'amount' => 500.00,
            'notes' => 'Returned part of advance',
        ]);
        $this->assertEquals(3500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(1500.00, (float) $this->labour->fresh()->advance_amt);
    }
}
