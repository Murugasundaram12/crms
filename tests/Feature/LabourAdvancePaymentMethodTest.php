<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\TransferDetails;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourAdvancePaymentMethodTest extends TestCase
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
            'wallet' => 10000.00,
        ]);
        $this->user->roles()->sync([$role->id]);
        $this->user->clearResolvedPermissions();

        $labourRole = LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);

        $this->labour = Labour::create([
            'name' => 'Test Labour ' . uniqid(),
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $labourRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $this->paymentMethod = PaymentMethod::query()->firstOrCreate(
            ['name' => 'GPay Test ' . uniqid()],
            ['code' => 'GPAY_' . strtoupper(uniqid()), 'type' => 'online', 'active_status' => true]
        );
    }

    /**
     * TEST 1 — Payment Method Required
     * Attempt direct Labour Advance without payment_method_id.
     */
    public function test_payment_method_is_required_for_direct_labour_advance(): void
    {
        $initialUserWallet = (float) $this->user->wallet;
        $initialAdvance = (float) $this->labour->advance_amt;
        $initialHistoryCount = AdvanceHistory::where('labour_id', $this->labour->id)->count();
        $initialWalletCount = Wallet::where('user_id', $this->user->id)->count();

        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'notes' => 'Advance without payment method',
            // payment_method_id intentionally omitted
        ]);

        $response->assertSessionHasErrors(['payment_method_id']);

        // Assert no financial changes
        $this->assertEquals($initialUserWallet, (float) $this->user->fresh()->wallet);
        $this->assertEquals($initialAdvance, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialHistoryCount, AdvanceHistory::where('labour_id', $this->labour->id)->count());
        $this->assertEquals($initialWalletCount, Wallet::where('user_id', $this->user->id)->count());
    }

    /**
     * TEST 2 — Valid Payment Method
     * Employee wallet: ₹10,000, Labour advance: ₹0, Advance: ₹2,000, Payment Method: GPay
     */
    public function test_direct_labour_advance_with_valid_payment_method_succeeds_and_stores_in_wallet_ledger(): void
    {
        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Advance paid via ' . $this->paymentMethod->name,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 1. Employee wallet: ₹8,000
        $this->assertEquals(8000.00, (float) $this->user->fresh()->wallet);

        // 2. Labour advance: ₹2,000
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // 3. AdvanceHistory: credit ₹2,000
        $history = AdvanceHistory::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals('credit', $history->entry_type);
        $this->assertEquals(2000.00, (float) $history->amount);
        $this->assertEquals($this->user->id, $history->user_id);

        // 4. Wallet ledger: source_type = labour_advance, payment_method_id = selected method
        $walletEntry = Wallet::where('user_id', $this->user->id)
            ->where('source_type', 'labour_advance')
            ->where('source_id', $history->id)
            ->first();

        $this->assertNotNull($walletEntry);
        $this->assertEquals(2000, $walletEntry->amount);
        $this->assertEquals(1, $walletEntry->transfer_type); // Debit
        $this->assertEquals($this->paymentMethod->id, $walletEntry->payment_method_id);
        $this->assertEquals($this->paymentMethod->id, $walletEntry->payment_mode);
        // Confirm hard-coded 1 is replaced
        $this->assertNotEquals(0, $walletEntry->payment_method_id);
    }

    /**
     * TEST 3 — Invalid Payment Method
     * Use a non-existing payment_method_id.
     */
    public function test_invalid_payment_method_is_rejected(): void
    {
        $initialUserWallet = (float) $this->user->wallet;
        $initialAdvance = (float) $this->labour->advance_amt;

        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 2000.00,
            'payment_method_id' => 9999999, // Invalid
            'notes' => 'Advance with invalid payment method',
        ]);

        $response->assertSessionHasErrors(['payment_method_id']);
        $this->assertEquals($initialUserWallet, (float) $this->user->fresh()->wallet);
        $this->assertEquals($initialAdvance, (float) $this->labour->fresh()->advance_amt);
        $this->assertDatabaseMissing('advance_history', ['labour_id' => $this->labour->id]);
    }

    /**
     * TEST 4 — Existing Direct Advance Behavior
     * Wallet debit, advance increase, AdvanceHistory, Wallet ledger, and subsequent withdrawal.
     */
    public function test_existing_direct_advance_and_withdrawal_continue_working(): void
    {
        // Give advance ₹3,000
        $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $this->assertEquals(7000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);

        // Withdraw ₹1,000
        $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'withdraw',
            'amount' => 1000.00,
            'notes' => 'Returned part of advance',
        ]);

        // Wallet refunded to ₹8,000, Labour advance reduced to ₹2,000
        $this->assertEquals(8000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        $withdrawHistory = AdvanceHistory::where('labour_id', $this->labour->id)
            ->where('entry_type', 'withdraw')
            ->first();
        $this->assertNotNull($withdrawHistory);
        $this->assertEquals(1000.00, (float) $withdrawHistory->amount);
    }

    /**
     * TEST 5 — Normal Labour Transfer Regression
     * Stage 1 normal transfer does NOT modify advance_amt and does NOT create AdvanceHistory credit.
     */
    public function test_normal_labour_transfer_stage_1_behavior_is_preserved(): void
    {
        $this->labour->update(['advance_amt' => 2000.00]);
        $this->user->update(['wallet' => 8000.00]);

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

        // Employee wallet debited by ₹1,000
        $this->assertEquals(7000.00, (float) $this->user->fresh()->wallet);

        // Labour advance_amt remains UNCHANGED at ₹2,000
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // AdvanceHistory does NOT receive a credit entry from normal transfer
        $this->assertEquals(0, AdvanceHistory::where('labour_id', $this->labour->id)->count());

        // Transferdetails has the transfer record
        $this->assertDatabaseHas('transferdetails', [
            'user_id' => $this->user->id,
            'labour_id' => $this->labour->id,
            'transfer_type' => 'labour',
            'amount' => 1000,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
    }

    /**
     * TEST 6 — Existing Salary Regression
     * Salary advance adjustment strictly considers actual advance balance.
     */
    public function test_salary_advance_adjustment_regression(): void
    {
        $this->labour->update(['advance_amt' => 2000.00, 'salary' => 3000.00]);
        $this->user->update(['wallet' => 10000.00]);

        // Attempting to adjust ₹2,500 exceeds available advance of ₹2,000
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

        // Adjusting exactly ₹2,000 succeeds
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
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
    }
}
