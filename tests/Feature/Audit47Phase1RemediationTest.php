<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Client;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSalary;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Preorder;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\ToolMaterial;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Audit47Phase1RemediationTest extends TestCase
{
    private User $adminUser;
    private User $employeeUser;
    private PaymentMethod $paymentMethod;
    private Project $project;
    private Client $client;
    private PaymentStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');
        $this->withoutMiddleware([
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
            \App\Http\Middleware\CheckPermission::class,
        ]);

        foreach ([
            'advance_history',
            'wallet',
            'labour_attendances',
            'labour_salaries',
            'employee_salaries',
            'expenses',
            'preorder_advances',
            'preorder_deliveries',
            'preorders',
            'tools_materials',
            'tool_material_assignments',
            'payments',
            'payment_stages',
            'quotation_items',
            'quotation_terms',
            'quotations',
            'categories',
            'main_categories',
            'labours',
            'vendors',
            'projects',
            'clients',
            'payment_methods',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('Super Admin');
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('password')->default('secret');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('active_status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('client_id')->nullable();
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('advance_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('labours', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('daily_rate', 10, 2)->default(500);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('main_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('main_category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('payment_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('stage_name')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_code')->nullable();
            $table->string('invoice_number')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('stage_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('completed');
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('client_id')->default(0);
            $table->foreignId('project_id')->default(0);
            $table->foreignId('stage_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->integer('payment_mode')->default(1);
            $table->foreignId('payment_method_id')->nullable();
            $table->tinyInteger('transfer_type')->default(0); // 0 = credit, 1 = debit
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->default(0);
            $table->string('description')->nullable();
            $table->date('current_date')->nullable();
            $table->tinyInteger('active_status')->default(1);
            $table->tinyInteger('delete_status')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('main_category_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->foreignId('labour_id')->nullable();
            $table->integer('payment_mode')->nullable();
            $table->tinyInteger('is_advance')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('paid_amt', 14, 2)->default(0);
            $table->decimal('unpaid_amt', 14, 2)->default(0);
            $table->decimal('extra_amt', 14, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('editedBy')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('current_date')->nullable();
            $table->string('image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('advance_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('labour_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
            $table->unsignedBigInteger('labour_salary_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('entry_type'); // credit, settle, withdraw, reverse
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->date('current_date')->nullable();
            $table->string('current_time', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('employee_salaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name')->nullable();
            $table->string('salary_period')->nullable();
            $table->decimal('salary', 14, 2)->nullable();
            $table->string('salary_type')->nullable();
            $table->decimal('monthly_salary', 14, 2)->nullable();
            $table->integer('working_days')->nullable();
            $table->decimal('present_days', 14, 2)->nullable();
            $table->integer('half_days')->nullable();
            $table->integer('paid_leave_days')->nullable();
            $table->integer('unpaid_leave_days')->nullable();
            $table->integer('absent_days')->nullable();
            $table->decimal('per_day_salary', 14, 2)->nullable();
            $table->decimal('gross_salary', 14, 2)->nullable();
            $table->decimal('half_day_deduction', 14, 2)->nullable();
            $table->decimal('unpaid_leave_deduction', 14, 2)->nullable();
            $table->decimal('absent_deduction', 14, 2)->nullable();
            $table->decimal('attendance_deduction', 14, 2)->nullable();
            $table->decimal('other_deductions', 14, 2)->nullable();
            $table->decimal('overtime_amount', 14, 2)->nullable();
            $table->decimal('net_salary', 14, 2)->nullable();
            $table->dateTime('calculated_at')->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->foreignId('paid_by')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->string('status')->default('paid');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('labour_salaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('labour_id');
            $table->date('salary_period_start')->nullable();
            $table->date('salary_period_end')->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('advance_adjusted', 14, 2)->default(0);
            $table->boolean('advance_paid')->default(false);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->foreignId('paid_by')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->string('status')->default('paid');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('labour_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('labour_id');
            $table->foreignId('project_id')->nullable();
            $table->date('attendance_date');
            $table->string('status')->default('present');
            $table->unsignedBigInteger('labour_salary_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tools_materials', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('item_type')->default('material');
            $table->string('unit')->default('pcs');
            $table->decimal('opening_quantity', 14, 2)->default(0);
            $table->decimal('opening_amount', 14, 2)->default(0);
            $table->decimal('reorder_level', 14, 2)->default(0);
            $table->boolean('active_status')->default(true);
            $table->timestamps();
        });

        Schema::create('preorders', function (Blueprint $table): void {
            $table->id();
            $table->string('preorder_number')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('tool_material_id')->nullable();
            $table->string('status')->default('draft');
            $table->string('delivery_status')->default('pending');
            $table->date('preorder_date')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('preorder_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('preorder_id');
            $table->decimal('delivered_quantity', 14, 2)->default(0);
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });

        Schema::create('preorder_advances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('preorder_id');
            $table->decimal('advance_amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        // Seed common fixtures
        $this->adminUser = User::create(['name' => 'Admin User', 'email' => 'admin@test.com', 'role' => 'Super Admin', 'wallet' => 50000]);
        $this->employeeUser = User::create(['name' => 'Employee User', 'email' => 'employee@test.com', 'role' => 'Employee', 'wallet' => 20000]);
        $this->paymentMethod = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'active_status' => true]);
        $this->client = Client::create(['name' => 'Test Client']);
        $this->project = Project::create(['name' => 'Test Project', 'client_id' => $this->client->id]);
        $this->stage = PaymentStage::create(['stage_name' => 'Initial Stage', 'name' => 'Initial Stage', 'project_id' => $this->project->id]);
    }

    // -------------------------------------------------------------------------
    // DEF-01: Vendor Expense Deletion & Advance Restoration
    // -------------------------------------------------------------------------

    public function test_def_01_vendor_expense_deletion_restores_advance_and_refunds_only_cash(): void
    {
        $this->actingAs($this->adminUser);

        $vendor = Vendor::create(['name' => 'Cement Vendor', 'advance_amt' => 500, 'advance_amount' => 500]);
        $mainCat = MainCategory::create(['name' => 'Materials']);
        $cat = Category::create(['name' => 'Cement', 'main_category_id' => $mainCat->id]);

        $initialWallet = (float) $this->adminUser->wallet; // 50000

        // Store vendor expense: total amount = 1000, paid amount = 1000
        // Existing advance = 500 => advanceUsed = 500, netWalletDebit = 500
        $response = $this->post(route('vendor-expenses.store'), [
            'vendor_id' => $vendor->id,
            'category_id' => $cat->id,
            'amount' => 1000,
            'paid_amount' => 1000,
            'payment_method_id' => $this->paymentMethod->id,
            'description' => 'Test cement purchase',
        ]);
        $response->assertSessionHasNoErrors();

        $expense = Expense::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $vendor->fresh()->advance_amt);
        $this->assertEquals($initialWallet - 500.0, (float) $this->adminUser->fresh()->wallet);

        // Advance history shows withdraw of 500
        $this->assertDatabaseHas('advance_history', [
            'vendor_id' => $vendor->id,
            'labour_expense_transaction_id' => $expense->id,
            'entry_type' => 'withdraw',
            'amount' => 500,
        ]);

        // Delete the expense
        $delResponse = $this->post(route('vendor-expenses.delete-record'), [
            'id' => $expense->id,
            'delete_reason' => 'Vendor cancelled order',
        ]);
        $delResponse->assertSessionHasNoErrors();

        // INVARIANT 1: Vendor advance restored to 500
        $this->assertEquals(500.0, (float) $vendor->fresh()->advance_amt);

        // INVARIANT 2: User wallet refunded ONLY the 500 cash debited (NOT 1000!)
        $this->assertEquals($initialWallet, (float) $this->adminUser->fresh()->wallet);

        // INVARIANT 3: AdvanceHistory record logged for restoration
        $this->assertDatabaseHas('advance_history', [
            'vendor_id' => $vendor->id,
            'labour_expense_transaction_id' => $expense->id,
            'entry_type' => 'credit',
            'amount' => 500,
        ]);

        // INVARIANT 4: Expense marked soft-deleted
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_def_01_vendor_expense_deletion_with_extra_advance_reversed(): void
    {
        $this->actingAs($this->adminUser);

        $vendor = Vendor::create(['name' => 'Steel Vendor', 'advance_amt' => 0, 'advance_amount' => 0]);
        $mainCat = MainCategory::create(['name' => 'Steel']);
        $cat = Category::create(['name' => 'Rods', 'main_category_id' => $mainCat->id]);

        $initialWallet = (float) $this->adminUser->wallet;

        // Store expense: Amount 800, Paid 1000 => Extra 200 added as vendor advance
        $response = $this->post(route('vendor-expenses.store'), [
            'vendor_id' => $vendor->id,
            'category_id' => $cat->id,
            'amount' => 800,
            'paid_amount' => 1000,
            'payment_method_id' => $this->paymentMethod->id,
            'description' => 'Overpayment advance',
        ]);
        $response->assertSessionHasNoErrors();

        $expense = Expense::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertEquals(200.0, (float) $vendor->fresh()->advance_amt);
        $this->assertEquals($initialWallet - 1000.0, (float) $this->adminUser->fresh()->wallet);

        // Delete expense
        $delResponse = $this->post(route('vendor-expenses.delete-record'), [
            'id' => $expense->id,
            'delete_reason' => 'Overpayment error',
        ]);
        $delResponse->assertSessionHasNoErrors();

        // Extra advance is deducted back to 0
        $this->assertEquals(0.0, (float) $vendor->fresh()->advance_amt);
        // User gets full 1000 back
        $this->assertEquals($initialWallet, (float) $this->adminUser->fresh()->wallet);
        // Withdraw history logged for extra reversal
        $this->assertDatabaseHas('advance_history', [
            'vendor_id' => $vendor->id,
            'labour_expense_transaction_id' => $expense->id,
            'entry_type' => 'withdraw',
            'amount' => 200,
        ]);
    }

    // -------------------------------------------------------------------------
    // DEF-02: Payment Deletion & Wallet Attribution
    // -------------------------------------------------------------------------

    public function test_def_02_payment_deletion_by_different_user_debits_original_recipient(): void
    {
        // 1. Employee records a payment of 5000
        $this->actingAs($this->employeeUser);
        $initialEmployeeWallet = (float) $this->employeeUser->fresh()->wallet; // 20000
        $initialAdminWallet = (float) $this->adminUser->fresh()->wallet;       // 50000

        $quotation = Quotation::create([
            'quotation_number' => 'QTN-0001',
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'total_amount' => 10000,
            'amount' => 10000,
        ]);

        $response = $this->post(route('payments.store'), [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $quotation->id,
            'stage_id' => $this->stage->id,
            'amount' => 5000,
            'status' => 'paid',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $response->assertSessionHasNoErrors();

        $payment = Payment::latest('id')->firstOrFail();

        // Employee wallet credited by 5000
        $this->assertEquals($initialEmployeeWallet + 5000, (float) $this->employeeUser->fresh()->wallet);
        $this->assertEquals($initialAdminWallet, (float) $this->adminUser->fresh()->wallet);

        // 2. Admin B deletes the payment
        $this->actingAs($this->adminUser);
        $delResponse = $this->delete(route('payments.destroy', $payment->id));
        $delResponse->assertSessionHasNoErrors();

        // INVARIANT 1: Original recipient (Employee) wallet is debited back 5000
        $this->assertEquals($initialEmployeeWallet, (float) $this->employeeUser->fresh()->wallet);

        // INVARIANT 2: Admin B wallet remains completely untouched at 50000
        $this->assertEquals($initialAdminWallet, (float) $this->adminUser->fresh()->wallet);

        // INVARIANT 3: Rollback ledger record attributed to Employee
        $this->assertDatabaseHas('wallet', [
            'user_id' => $this->employeeUser->id,
            'source_type' => 'payment_rollback',
            'source_id' => $payment->id,
            'transfer_type' => 1,
            'amount' => 5000,
        ]);
    }

    public function test_def_02_payment_deletion_aborts_when_original_user_has_insufficient_balance(): void
    {
        // Employee records payment of 5000
        $this->actingAs($this->employeeUser);
        $quotation = Quotation::create([
            'quotation_number' => 'QTN-0002',
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'total_amount' => 10000,
            'amount' => 10000,
        ]);

        $this->post(route('payments.store'), [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $quotation->id,
            'stage_id' => $this->stage->id,
            'amount' => 5000,
            'status' => 'paid',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $payment = Payment::latest('id')->firstOrFail();

        // Drain employee wallet down to 100
        $this->employeeUser->update(['wallet' => 100]);

        // Admin B tries to delete the 5000 payment
        $this->actingAs($this->adminUser);

        try {
            $this->withoutExceptionHandling();
            $this->delete(route('payments.destroy', $payment->id));
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertTrue(true, 'ValidationException was properly thrown.');
        }

        // INVARIANT: Payment is not deleted; transaction rolled back
        $this->assertNotNull(Payment::find($payment->id));
        $this->assertEquals(100.0, (float) $this->employeeUser->fresh()->wallet);
        $this->assertEquals(50000.0, (float) $this->adminUser->fresh()->wallet);
    }

    public function test_def_02_payment_deletion_aborts_when_ledger_linkage_missing(): void
    {
        $this->actingAs($this->adminUser);
        // Create payment directly without wallet entry
        $payment = Payment::create([
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'amount' => 2000,
            'status' => 'paid',
        ]);

        try {
            $this->withoutExceptionHandling();
            $this->delete(route('payments.destroy', $payment->id));
            $this->fail('Expected ValidationException for missing ledger linkage.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('missing ledger linkage', $e->getMessage());
        }

        // Payment remains intact
        $this->assertNotNull(Payment::find($payment->id));
    }

    // -------------------------------------------------------------------------
    // DEF-03: Salary Deletion & Ledger History
    // -------------------------------------------------------------------------

    public function test_def_03_employee_salary_deletion_refunds_payer_wallet(): void
    {
        $this->actingAs($this->adminUser);
        $initialAdminWallet = (float) $this->adminUser->wallet;

        // Store employee salary of 3000
        $response = $this->post(route('employee-salaries.store'), [
            'user_id' => $this->employeeUser->id,
            'salary_period' => '2026-10',
            'working_days' => 26,
            'net_salary' => 3000,
            'salary_amount' => 3000,
            'paid_amount' => 3000,
            'payment_date' => '2026-10-26',
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $response->assertSessionHasNoErrors();

        $salary = EmployeeSalary::where('user_id', $this->employeeUser->id)->firstOrFail();
        $this->assertEquals($initialAdminWallet - 3000, (float) $this->adminUser->fresh()->wallet);

        // Delete salary by another user or same admin
        $delResponse = $this->delete(route('employee-salaries.destroy', $salary->id));
        $delResponse->assertSessionHasNoErrors();

        // Payer wallet refunded 3000
        $this->assertEquals($initialAdminWallet, (float) $this->adminUser->fresh()->wallet);
        // Salary deleted
        $this->assertNull(EmployeeSalary::find($salary->id));

        // Duplicate deletion returns 404
        $dupResponse = $this->delete(route('employee-salaries.destroy', $salary->id));
        $dupResponse->assertNotFound();
    }

    public function test_def_03_labour_salary_deletion_restores_advance_and_payer_wallet(): void
    {
        $this->actingAs($this->adminUser);
        $labour = Labour::create(['name' => 'Ravi Kumar', 'advance_amt' => 2000]);
        $initialAdminWallet = (float) $this->adminUser->wallet;

        $att = LabourAttendance::create([
            'labour_id' => $labour->id,
            'attendance_date' => '2026-10-20',
            'status' => 'present',
        ]);

        // Create labour salary with advance adjustment = 1000, paid cash = 1000
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $labour->id,
            'salary_amount' => 2000,
            'advance_paid' => '1',
            'advance_adjusted' => 1000,
            'paid_amount' => 1000,
            'salary_period_start' => '2026-10-20',
            'salary_period_end' => '2026-10-20',
            'payment_date' => '2026-10-26',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => [$att->id],
        ]);
        $response->assertSessionHasNoErrors();

        $salary = LabourSalary::where('labour_id', $labour->id)->firstOrFail();
        $this->assertEquals(1000.0, (float) $labour->fresh()->advance_amt);
        $this->assertEquals($initialAdminWallet - 1000, (float) $this->adminUser->fresh()->wallet);

        // Delete salary
        $delResponse = $this->delete(route('labour-salaries.destroy', $salary->id));
        $delResponse->assertSessionHasNoErrors();

        // Advance restored to 2000
        $this->assertEquals(2000.0, (float) $labour->fresh()->advance_amt);
        // Payer wallet refunded 1000
        $this->assertEquals($initialAdminWallet, (float) $this->adminUser->fresh()->wallet);
        // Salary deleted
        $this->assertNull(LabourSalary::find($salary->id));
    }

    // -------------------------------------------------------------------------
    // DEF-04: Reports Empty-State Colspan
    // -------------------------------------------------------------------------

    public function test_def_04_reports_empty_state_colspan_matches_role(): void
    {
        $this->actingAs($this->adminUser); // Super Admin role
        $viewSuperAdmin = view('pages.reports.index', [
            'records' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
            'summary' => ['count' => 0, 'total_amount' => 0, 'paid' => 0, 'unpaid' => 0],
            'type' => 'daily',
            'filters' => [],
            'dateFrom' => '2026-10-01',
            'dateTo' => '2026-10-26',
            'projects' => collect(),
            'mainCategories' => collect(),
            'subCategories' => collect(),
            'labours' => collect(),
            'vendors' => collect(),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('colspan="14"', $viewSuperAdmin);

        $this->actingAs($this->employeeUser); // Non Super Admin role
        $viewNonSuperAdmin = view('pages.reports.index', [
            'records' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
            'summary' => ['count' => 0, 'total_amount' => 0, 'paid' => 0, 'unpaid' => 0],
            'type' => 'daily',
            'filters' => [],
            'dateFrom' => '2026-10-01',
            'dateTo' => '2026-10-26',
            'projects' => collect(),
            'mainCategories' => collect(),
            'subCategories' => collect(),
            'labours' => collect(),
            'vendors' => collect(),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('colspan="13"', $viewNonSuperAdmin);
        $this->assertStringNotContainsString('colspan="14"', $viewNonSuperAdmin);
    }

    // -------------------------------------------------------------------------
    // DEF-05: Preorder Performance & Database Aggregation
    // -------------------------------------------------------------------------

    public function test_def_05_preorder_metrics_aggregation_matches_semantic_counts(): void
    {
        $this->actingAs($this->adminUser);

        // Preorders with mixed statuses
        Preorder::create(['status' => Preorder::STATUS_DRAFT]);
        Preorder::create(['status' => Preorder::STATUS_DRAFT]);
        Preorder::create(['status' => Preorder::STATUS_PENDING_APPROVAL]);
        Preorder::create(['status' => Preorder::STATUS_APPROVED]);
        Preorder::create(['status' => Preorder::STATUS_APPROVED]);
        Preorder::create(['status' => Preorder::STATUS_ORDERED]);
        Preorder::create(['status' => Preorder::STATUS_DELIVERED]);
        Preorder::create(['status' => Preorder::STATUS_CANCELLED]);

        $response = $this->get(route('preorders.index'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        $this->assertSame(8, $metrics['total']);
        $this->assertSame(3, $metrics['pending_approval']); // 2 draft + 1 pending
        $this->assertSame(2, $metrics['approved']);
        $this->assertSame(1, $metrics['ordered']);
        $this->assertSame(1, $metrics['delivered']);
        $this->assertSame(1, $metrics['cancelled']);
        $this->assertSame(3, $metrics['pending_deliveries']); // 2 approved + 1 ordered
    }

    // -------------------------------------------------------------------------
    // DEF-06: Quotation Number Concurrency & Sequencing
    // -------------------------------------------------------------------------

    public function test_def_06_quotation_number_generation_sequential_and_duplicate_safe(): void
    {
        $this->actingAs($this->adminUser);

        // First quotation
        $q1 = DB::transaction(function () {
            $controller = new \App\Http\Controllers\QuotationController();
            $method = new \ReflectionMethod($controller, 'generateNextQuotationNumber');
            $method->setAccessible(true);
            $num = $method->invoke($controller);
            return Quotation::create(['quotation_number' => $num, 'client_id' => $this->client->id]);
        });
        $this->assertEquals('QTN-0001', $q1->quotation_number);

        // Second quotation
        $q2 = DB::transaction(function () {
            $controller = new \App\Http\Controllers\QuotationController();
            $method = new \ReflectionMethod($controller, 'generateNextQuotationNumber');
            $method->setAccessible(true);
            $num = $method->invoke($controller);
            return Quotation::create(['quotation_number' => $num, 'client_id' => $this->client->id]);
        });
        $this->assertEquals('QTN-0002', $q2->quotation_number);

        // Pre-create duplicate QTN-0003 manually
        Quotation::create(['quotation_number' => 'QTN-0003', 'client_id' => $this->client->id]);

        // Next generated should safely skip existing QTN-0003 to QTN-0004
        $q4 = DB::transaction(function () {
            $controller = new \App\Http\Controllers\QuotationController();
            $method = new \ReflectionMethod($controller, 'generateNextQuotationNumber');
            $method->setAccessible(true);
            $num = $method->invoke($controller);
            return Quotation::create(['quotation_number' => $num, 'client_id' => $this->client->id]);
        });
        $this->assertEquals('QTN-0004', $q4->quotation_number);
    }
}
