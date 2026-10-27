<?php

namespace Tests\Feature\Qa;

use App\Models\Category;
use App\Models\Client;
use App\Models\Expense;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Project;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class FinancialCalculationsAndWalletQaTest extends QaTestCase
{


    /**
     * Test Payment Creation and Stage Linking.
     */
    public function test_payment_creation_with_stage_and_method(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $stage = PaymentStage::factory()->create([
            'project_id' => $project->id,
            'stage_name' => 'Roof Slab Concrete',
        ]);
        $quotation = \App\Models\Quotation::factory()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'total_amount' => 150000.00,
        ]);
        $method = PaymentMethod::factory()->create(['name' => 'NEFT', 'active_status' => 1]);

        $paymentData = [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'quotation_id' => $quotation->id,
            'stage_id' => $stage->id,
            'payment_method_id' => $method->id,
            'method' => 'bank_transfer',
            'amount' => 75000.50,
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
        ];

        $response = $this->actingAs($admin)->post(route('payments.store'), $paymentData);
        $response->assertRedirect(route('payments.index'));

        $this->assertDatabaseHas('payments', [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'quotation_id' => $quotation->id,
            'stage_id' => $stage->id,
            'amount' => 75000.50,
            'status' => 'paid',
        ]);
    }

    /**
     * Test Expense Paid vs Unpaid Arithmetic Consistency.
     */
    public function test_expense_paid_and_unpaid_calculation_consistency(): void
    {
        $admin = $this->createSuperAdmin();
        $project = Project::factory()->create();
        $mainCat = MainCategory::factory()->create();
        $cat = Category::factory()->create(['main_category_id' => $mainCat->id]);

        $totalAmount = 5000.00;
        $paidAmount = 3000.00;
        $unpaidAmount = 2000.00;

        $expense = Expense::create([
            'project_id' => $project->id,
            'main_category_id' => $mainCat->id,
            'category_id' => $cat->id,
            'user_id' => $admin->id,
            'amount' => $totalAmount,
            'paid_amt' => $paidAmount,
            'unpaid_amt' => $unpaidAmount,
            'current_date' => now(),
            'description' => 'Electrical materials partial payment',
        ]);

        $this->assertEquals($totalAmount, $expense->paid_amt + $expense->unpaid_amt);
        $this->assertEquals('5000.00', number_format($expense->amount, 2, '.', ''));
    }

    /**
     * Test Wallet Balance Adjustment Integrity.
     */
    public function test_wallet_credit_and_debit_ledger_balance(): void
    {
        $admin = $this->createSuperAdmin(['wallet' => 10000.00]);

        // Credit to wallet
        Wallet::create([
            'user_id' => $admin->id,
            'client_id' => 0,
            'project_id' => 0,
            'amount' => 5000.00,
            'transfer_type' => 0, // credit
            'description' => 'Site imprest cash replenishment',
        ]);

        // Debit from wallet
        Wallet::create([
            'user_id' => $admin->id,
            'client_id' => 0,
            'project_id' => 0,
            'amount' => 2000.00,
            'transfer_type' => 1, // debit
            'description' => 'Material purchase debit',
        ]);

        $credits = Wallet::query()->where('user_id', $admin->id)->where('transfer_type', 0)->sum('amount');
        $debits = Wallet::query()->where('user_id', $admin->id)->where('transfer_type', 1)->sum('amount');

        $netWalletChange = $credits - $debits;
        $this->assertEquals(3000.00, $netWalletChange);
    }

    /**
     * Test Decimal Precision (No floating-point truncations).
     */
    public function test_currency_decimal_precision_is_maintained(): void
    {
        $admin = $this->createSuperAdmin();
        $cat = Category::factory()->create();

        $preciseAmount = 12345.67;

        $expense = Expense::create([
            'user_id' => $admin->id,
            'category_id' => $cat->id,
            'amount' => $preciseAmount,
            'paid_amt' => $preciseAmount,
            'unpaid_amt' => 0.00,
            'current_date' => now(),
            'description' => 'Precision test',
        ]);

        $freshExpense = Expense::find($expense->id);
        $this->assertEquals('12345.67', $freshExpense->amount);
    }
}
