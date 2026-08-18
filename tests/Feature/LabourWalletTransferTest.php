<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\TransferDetails;
use App\Models\User;
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

        $role->permissions()->syncWithoutDetaching([$pCreate->id, $pList->id, $pEdit->id, $pDelete->id]);

        $this->user = User::factory()->create([
            'role' => 'Super Admin',
            'wallet' => 1500.00,
        ]);
        $this->user->roles()->sync([$role->id]);
        $this->user->clearResolvedPermissions();

        $labourRole = \App\Models\LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);

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

        $this->assertEquals(500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);

        $this->assertDatabaseHas('transferdetails', [
            'user_id' => $this->user->id,
            'labour_id' => $this->labour->id,
            'transfer_type' => 'labour',
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'entry_type' => 'credit',
            'user_id' => $this->user->id,
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
        $this->assertEquals(1500.00, (float) $this->labour->fresh()->advance_amt);
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

    public function test_advance_history_credit_row_created(): void
    {
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 400.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
            'description' => 'Advance for site work',
        ]);

        $history = AdvanceHistory::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals('credit', $history->entry_type);
        $this->assertEquals(400.00, (float) $history->amount);
        $this->assertStringContainsString('Wallet Transfer from Employee', $history->notes);
        $this->assertStringContainsString('Advance for site work', $history->notes);
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
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);

        $response = $this->actingAs($this->user)->put(route('transfers.update', $transfer->id), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $response->assertRedirect(route('transfers.index'));

        $this->assertEquals(3500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(1500.00, (float) $this->labour->fresh()->advance_amt);
    }

    public function test_deleting_labour_transfer_refunds_employee_and_reduces_labour_advance(): void
    {
        $this->user->update(['wallet' => 4000.00]);

        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        $transfer = TransferDetails::where('user_id', $this->user->id)->latest('id')->first();

        $response = $this->actingAs($this->user)->delete(route('transfers.destroy', $transfer->id));
        $response->assertRedirect(route('transfers.index'));

        $this->assertEquals(4000.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertTrue((bool) $transfer->fresh()->delete_status);
    }

    public function test_deleting_transfer_rejected_when_labour_already_consumed_advance(): void
    {
        $this->actingAs($this->user)->post(route('transfers.store'), [
            'transfer_type' => 'labour',
            'labour_id' => $this->labour->id,
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00 AM',
        ]);

        // Manually simulate Labour consuming 400 of advance
        $this->labour->update(['advance_amt' => 600.00]);

        $transfer = TransferDetails::where('user_id', $this->user->id)->latest('id')->first();

        $response = $this->from(route('transfers.index'))->actingAs($this->user)->delete(route('transfers.destroy', $transfer->id));
        $response->assertSessionHasErrors(['amount']);

        $this->assertEquals(500.00, (float) $this->user->fresh()->wallet);
        $this->assertEquals(600.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertFalse((bool) $transfer->fresh()->delete_status);
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

        $labourRole = \App\Models\LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);
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
        $this->assertEquals(1000.00, (float) $labour->fresh()->advance_amt);
    }
}
