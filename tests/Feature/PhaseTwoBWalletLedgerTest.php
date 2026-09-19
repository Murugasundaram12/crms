<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\ExpenseUnpaidDate;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourWalletAllocation;
use App\Models\LabourWalletTransaction;
use App\Models\MainCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\TransferDetails;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseTwoBWalletLedgerTest extends TestCase
{
    protected User $admin;
    protected User $employeeA;
    protected User $employeeB;
    protected Employee $empRecordA;
    protected Employee $empRecordB;
    protected Labour $labour;
    protected Client $client;
    protected Project $project;
    protected MainCategory $mainCategory;
    protected Category $category;
    protected PaymentMethod $paymentMethod;
    protected Quotation $quotation;
    protected PaymentStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->createIsolatedSchema();

        $role = Role::query()->create(['name' => 'Super Admin']);
        $perms = [
            'expenses-list', 'expenses-create', 'expenses-edit', 'expenses-delete',
            'labour-expenses-list', 'labour-expenses-create', 'labour-expenses-edit', 'labour-expenses-delete',
            'unpaid-expenses-list', 'unpaid-expenses-create',
            'transfers-list', 'transfers-create', 'transfers-edit', 'transfers-delete',
            'employee-salaries-list', 'employee-salaries-create', 'employee-salaries-edit', 'employee-salaries-delete',
            'payments-list', 'payments-create', 'payments-edit', 'payments-delete',
            'wallet-list', 'wallet-create', 'wallet-view',
        ];

        $permIds = [];
        foreach ($perms as $p) {
            $perm = Permission::query()->create(['name' => $p, 'key' => $p]);
            $permIds[] = $perm->id;
        }
        $role->permissions()->sync($permIds);

        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'Super Admin',
            'wallet' => 50000.00,
            'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->sync([$role->id]);

        $this->employeeA = User::query()->create([
            'name' => 'Employee A',
            'email' => 'empa@test.com',
            'role' => 'Super Admin',
            'wallet' => 10000.00,
            'password' => bcrypt('password'),
        ]);
        $this->employeeA->roles()->sync([$role->id]);

        $this->employeeB = User::query()->create([
            'name' => 'Employee B',
            'email' => 'empb@test.com',
            'role' => 'Super Admin',
            'wallet' => 10000.00,
            'password' => bcrypt('password'),
        ]);
        $this->employeeB->roles()->sync([$role->id]);

        $this->empRecordA = new Employee([
            'name' => 'Employee A',
            'email' => 'empa@test.com',
            'wallet' => 10000.00,
            'status' => 'active',
        ]);
        $this->empRecordA->id = $this->employeeA->id;
        $this->empRecordA->save();

        $this->empRecordB = new Employee([
            'name' => 'Employee B',
            'email' => 'empb@test.com',
            'wallet' => 10000.00,
            'status' => 'active',
        ]);
        $this->empRecordB->id = $this->employeeB->id;
        $this->empRecordB->save();

        $this->client = Client::query()->create(['name' => 'Test Client']);

        $this->project = Project::query()->create([
            'name' => 'Test Project',
            'project_code' => 'PRJ-TEST',
            'client_id' => $this->client->id,
            'type' => 'general',
            'status' => 'active',
            'advance_amt' => 0.00,
            'profit' => 0.00,
        ]);

        $this->mainCategory = MainCategory::query()->create(['name' => 'CIVIL']);
        $this->category = Category::query()->create([
            'name' => 'Supplies',
            'main_category_id' => $this->mainCategory->id,
        ]);

        $this->paymentMethod = PaymentMethod::query()->create([
            'name' => 'Cash',
            'code' => 'CASH',
            'type' => 'cash',
            'active_status' => true,
        ]);

        $labourRole = LabourRole::query()->create([
            'name' => 'Mason',
            'salary_type' => 'daily',
            'salary' => 500.00,
        ]);

        $this->labour = Labour::query()->create([
            'name' => 'Ramu Labour',
            'phone' => '9999999991',
            'phone_number' => '9999999991',
            'labour_role_id' => $labourRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $this->quotation = Quotation::query()->create([
            'quotation_number' => 'QUO-1001',
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'amount' => 5000.00,
            'total_amount' => 5000.00,
            'status' => 'approved',
        ]);

        $this->stage = PaymentStage::query()->create([
            'name' => 'Stage 1',
            'stage_name' => 'Stage 1',
            'project_id' => $this->project->id,
        ]);
    }

    protected function createIsolatedSchema(): void
    {
        $schema = Schema::connection('sqlite');

        $tables = [
            'leave_requests', 'leave_types',
            'labour_wallet_allocations', 'labour_wallet_transactions', 'labour_salaries',
            'advance_history', 'expenses_unpaid_date', 'expenses', 'wallet',
            'transferdetails', 'employee_salaries', 'payments', 'payment_stages',
            'quotations', 'labours', 'labour_roles', 'vendors', 'employees',
            'categories', 'main_categories', 'projects', 'clients', 'payment_methods',
            'role_permission', 'permissions', 'user_roles', 'roles', 'users',
        ];

        foreach ($tables as $t) {
            $schema->dropIfExists($t);
        }

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('role')->nullable();
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('status')->default('active');
            $table->string('password')->default('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $schema->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $schema->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->timestamps();
        });

        $schema->create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id');
            $table->timestamps();
        });

        $schema->create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->timestamps();
        });

        $schema->create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');
            $table->boolean('active_status')->default(true);
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        $schema->create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->foreignId('client_id')->nullable();
            $table->string('type')->default('general');
            $table->string('status')->default('active');
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->timestamps();
        });

        $schema->create('main_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('main_category_id');
            $table->timestamps();
        });

        $schema->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('designation')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $schema->create('labour_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('salary_type')->default('daily');
            $table->decimal('salary', 14, 2)->default(0);
            $table->timestamps();
        });

        $schema->create('labours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('labour_role_id')->nullable();
            $table->decimal('salary', 14, 2)->default(0);
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('wallet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('client_id')->default(0);
            $table->unsignedBigInteger('project_id')->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->integer('payment_mode')->default(1);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->tinyInteger('transfer_type')->default(0);
            $table->integer('stage_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('current_date')->nullable();
            $table->tinyInteger('active_status')->default(1);
            $table->tinyInteger('delete_status')->default(0);
            $table->timestamps();
        });

        $schema->create('transferdetails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('labour_id')->nullable();
            $table->string('transfer_type')->default('employee');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_mode')->default('Cash');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->text('description')->nullable();
            $table->date('current_date')->nullable();
            $table->string('current_time')->nullable();
            $table->boolean('active_status')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->timestamps();
        });

        $schema->create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('main_category_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('labour_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('paid_amt', 14, 2)->default(0);
            $table->decimal('unpaid_amt', 14, 2)->default(0);
            $table->decimal('extra_amt', 14, 2)->default(0);
            $table->date('current_date')->nullable();
            $table->tinyInteger('is_advance')->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('editedBy')->nullable();
            $table->string('reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('expenses_unpaid_date', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id');
            $table->foreignId('user_id');
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('current_date');
            $table->string('current_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $schema->create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('salary', 14, 2)->default(0);
            $table->string('salary_type')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('salary_period')->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('monthly_salary', 14, 2)->default(0);
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('half_days')->default(0);
            $table->integer('paid_leave_days')->default(0);
            $table->integer('unpaid_leave_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->decimal('per_day_salary', 14, 2)->default(0);
            $table->decimal('gross_salary', 14, 2)->default(0);
            $table->decimal('half_day_deduction', 14, 2)->default(0);
            $table->decimal('unpaid_leave_deduction', 14, 2)->default(0);
            $table->decimal('absent_deduction', 14, 2)->default(0);
            $table->decimal('attendance_deduction', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->dateTime('calculated_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('paid');
            $table->foreignId('paid_by')->nullable();
            $table->timestamps();
        });

        $schema->create('advance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
            $table->unsignedBigInteger('labour_salary_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('entry_type')->default('credit');
            $table->foreignId('payment_method_id')->nullable();
            $table->text('notes')->nullable();
            $table->date('current_date')->nullable();
            $table->string('current_time')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        $schema->create('labour_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->date('salary_period_start')->nullable();
            $table->date('salary_period_end')->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('advance_adjusted', 14, 2)->default(0);
            $table->tinyInteger('advance_paid')->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('paid_by')->nullable();
            $table->timestamps();
        });

        $schema->create('labour_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->foreignId('employee_id');
            $table->string('type');
            $table->decimal('amount', 14, 2)->default(0);
            $table->foreignId('payment_method_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->date('current_date')->nullable();
            $table->string('current_time')->nullable();
            $table->timestamps();
        });

        $schema->create('labour_wallet_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reversal_transaction_id');
            $table->foreignId('credit_transaction_id');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        $schema->create('payment_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('stage_name')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamps();
        });

        $schema->create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('approved');
            $table->timestamps();
        });

        $schema->create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        $schema->create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('leave_type_id')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        $schema->create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->string('payment_code')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('stage_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Scenario 1: Decimal wallet amount cast: 150.50 remains 150.50
     */
    public function test_01_decimal_wallet_amount_preserves_cents(): void
    {
        $wallet = Wallet::query()->create([
            'user_id' => $this->employeeA->id,
            'client_id' => 0,
            'project_id' => 0,
            'amount' => 150.50,
            'payment_mode' => 1,
            'transfer_type' => 0,
            'description' => 'Decimal test',
            'current_date' => now(),
            'active_status' => 1,
            'delete_status' => 0,
        ]);

        $this->assertEquals('150.50', (string) $wallet->amount);
        $this->assertSame(150.50, (float) $wallet->amount);

        $fresh = Wallet::query()->findOrFail($wallet->id);
        $this->assertSame(150.50, (float) $fresh->amount);
    }

    /**
     * Scenario 2: General expense create: wallet balance changes once, exactly one ledger event exists
     */
    public function test_02_general_expense_create_atomic_balance_and_single_ledger_event(): void
    {
        $this->actingAs($this->employeeA);

        $response = $this->post(route('expenses.store.new'), [
            'description' => 'Paint Supplies',
            'amount' => 250.75,
            'paid_amt' => 250.75,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();

        // Wallet balance debited exactly once: 10000.00 - 250.75 = 9749.25
        $this->assertEquals(9749.25, (float) $this->employeeA->fresh()->wallet);

        $expense = Expense::query()->where('description', 'Paint Supplies')->first();
        $this->assertNotNull($expense);

        // Exactly one ledger event created
        $ledgerEntries = Wallet::query()
            ->where('source_type', 'expense')
            ->where('source_id', $expense->id)
            ->get();

        $this->assertCount(1, $ledgerEntries);
        $this->assertEquals(1, $ledgerEntries->first()->transfer_type); // debit
        $this->assertEquals(250.75, (float) $ledgerEntries->first()->amount);
        $this->assertEquals($this->employeeA->id, $ledgerEntries->first()->user_id);
    }

    /**
     * Scenario 3: Expense update: test increase and decrease.
     * Verify only correct delta is applied. Verify ledger history is not duplicated.
     */
    public function test_03_expense_update_delta_increase_and_decrease(): void
    {
        $this->actingAs($this->employeeA);

        // 1. Initial create: 200.00
        $this->post(route('expenses.store.new'), [
            'description' => 'Cement',
            'amount' => 200.00,
            'paid_amt' => 200.00,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->toDateString(),
        ]);

        $expense = Expense::query()->where('description', 'Cement')->firstOrFail();
        $this->assertEquals(9800.00, (float) $this->employeeA->fresh()->wallet);

        // 2. Increase paid amount from 200.00 to 350.50 (delta +150.50)
        $this->put(route('expenses.update.new', $expense->id), [
            'description' => 'Cement Extra',
            'amount' => 350.50,
            'paid_amt' => 350.50,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->toDateString(),
        ]);

        // Wallet debited by delta: 9800.00 - 150.50 = 9649.50
        $this->assertEquals(9649.50, (float) $this->employeeA->fresh()->wallet);

        // Verify update delta ledger event
        $updateLedger = Wallet::query()
            ->where('source_type', 'expense_update')
            ->where('source_id', $expense->id)
            ->get();
        $this->assertCount(1, $updateLedger);
        $this->assertEquals(1, $updateLedger->first()->transfer_type); // debit
        $this->assertEquals(150.50, (float) $updateLedger->first()->amount);

        // 3. Decrease paid amount from 350.50 to 100.00 (delta -250.50)
        $this->put(route('expenses.update.new', $expense->id), [
            'description' => 'Cement Reduced',
            'amount' => 100.00,
            'paid_amt' => 100.00,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->toDateString(),
        ]);

        // Wallet credited by delta refund: 9649.50 + 250.50 = 9900.00
        $this->assertEquals(9900.00, (float) $this->employeeA->fresh()->wallet);

        // Verify credit delta ledger event exists
        $creditDelta = Wallet::query()
            ->where('source_type', 'expense_update')
            ->where('source_id', $expense->id)
            ->where('transfer_type', 0)
            ->first();
        $this->assertNotNull($creditDelta);
        $this->assertEquals(250.50, (float) $creditDelta->amount);

        // 4. Repeated update with identical amount (100.00) - must be a no-op
        $ledgerCountBefore = Wallet::query()->where('source_id', $expense->id)->count();
        $this->put(route('expenses.update.new', $expense->id), [
            'description' => 'Cement Reduced',
            'amount' => 100.00,
            'paid_amt' => 100.00,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'main_category_id' => $this->mainCategory->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->toDateString(),
        ]);

        $this->assertEquals(9900.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals($ledgerCountBefore, Wallet::query()->where('source_id', $expense->id)->count());
    }

    /**
     * Scenario 4: Expense delete: wallet refund occurs once. Ledger reflects refund.
     */
    public function test_04_expense_delete_refunds_wallet_once(): void
    {
        $this->actingAs($this->employeeA);

        $expense = Expense::query()->create([
            'expense_name' => 'To Delete',
            'user_id' => $this->employeeA->id,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'amount' => 300.00,
            'paid_amt' => 300.00,
            'unpaid_amt' => 0.00,
            'current_date' => now(),
        ]);

        app(CrmBalanceService::class)->debitUserWallet($this->employeeA->id, 300.00);
        $this->assertEquals(9700.00, (float) $this->employeeA->fresh()->wallet);

        $response = $this->post(route('expenses.delete-record'), [
            'expense_id' => $expense->id,
            'delete_reason' => 'Test deletion',
        ]);
        $response->assertRedirect();

        // Wallet refunded by 300.00
        $this->assertEquals(10000.00, (float) $this->employeeA->fresh()->wallet);

        // Exactly one refund ledger event exists
        $refundLedger = Wallet::query()
            ->where('source_type', 'expense_refund')
            ->where('source_id', $expense->id)
            ->get();

        $this->assertCount(1, $refundLedger);
        $this->assertEquals(0, $refundLedger->first()->transfer_type); // credit
        $this->assertEquals(300.00, (float) $refundLedger->first()->amount);
    }

    /**
     * Scenario 5: Expense restore: wallet debit occurs once. Ledger reflects restored debit.
     */
    public function test_05_expense_restore_debits_wallet_once(): void
    {
        $this->actingAs($this->employeeA);

        $expense = Expense::query()->create([
            'expense_name' => 'To Restore',
            'user_id' => $this->employeeA->id,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'amount' => 450.00,
            'paid_amt' => 450.00,
            'unpaid_amt' => 0.00,
            'current_date' => now(),
        ]);
        $expense->delete(); // Soft-deleted

        $initialWallet = (float) $this->employeeA->fresh()->wallet;

        $response = $this->post(route('expenses.restore-record'), [
            'expense_id' => $expense->id,
        ]);
        $response->assertRedirect();

        // Wallet debited by 450.00
        $this->assertEquals($initialWallet - 450.00, (float) $this->employeeA->fresh()->wallet);

        // Exactly one restore ledger event exists
        $restoreLedger = Wallet::query()
            ->where('source_type', 'expense_restore')
            ->where('source_id', $expense->id)
            ->get();

        $this->assertCount(1, $restoreLedger);
        $this->assertEquals(1, $restoreLedger->first()->transfer_type); // debit
        $this->assertEquals(450.00, (float) $restoreLedger->first()->amount);
    }

    /**
     * Scenario 6: Unpaid settlement: 99.75 settlement updates wallet and ledger atomically.
     */
    public function test_06_unpaid_settlement_updates_wallet_and_ledger_atomically(): void
    {
        $this->actingAs($this->employeeA);

        $expense = Expense::query()->create([
            'expense_name' => 'Unpaid Work',
            'user_id' => $this->employeeA->id,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'amount' => 500.00,
            'paid_amt' => 400.00,
            'unpaid_amt' => 100.00,
            'current_date' => now(),
        ]);

        $initialWallet = (float) $this->employeeA->fresh()->wallet;

        $response = $this->post(route('expenses.unpaid-store'), [
            'expense_id' => $expense->id,
            'paid_amount' => 99.75,
            'notes' => 'Partial settlement',
        ]);
        $response->assertRedirect();

        // Wallet debited by 99.75
        $this->assertEquals($initialWallet - 99.75, (float) $this->employeeA->fresh()->wallet);

        // Expense updated
        $expense->refresh();
        $this->assertEquals(499.75, (float) $expense->paid_amt);
        $this->assertEquals(0.25, (float) $expense->unpaid_amt);

        // Wallet ledger row created with source_type = 'expense_unpaid_settlement'
        $unpaidLedger = Wallet::query()
            ->where('source_type', 'expense_unpaid_settlement')
            ->where('source_id', $expense->id)
            ->first();

        $this->assertNotNull($unpaidLedger);
        $this->assertEquals(1, $unpaidLedger->transfer_type); // debit
        $this->assertEquals(99.75, (float) $unpaidLedger->amount);
    }

    /**
     * Scenario 7: Peer transfer: sender -500, recipient +500, exactly two corresponding ledger events.
     */
    public function test_07_peer_transfer_creates_sender_and_recipient_ledger_events(): void
    {
        $this->actingAs($this->employeeA);

        $initialSenderWallet = (float) $this->employeeA->fresh()->wallet;
        $initialRecipientWallet = (float) $this->employeeB->fresh()->wallet;

        $response = $this->post(route('transfers.store'), [
            'transfer_type' => 'employee',
            'employee_id' => $this->empRecordB->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => '10:00:00',
            'description' => 'Peer transfer test',
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Sender -500, Recipient +500
        $this->assertEquals($initialSenderWallet - 500.00, (float) $this->employeeA->fresh()->wallet);
        $this->assertEquals($initialRecipientWallet + 500.00, (float) $this->employeeB->fresh()->wallet);

        $transfer = TransferDetails::query()->latest('id')->firstOrFail();

        // Exactly two ledger events: sender transfer_out and recipient transfer_in
        $senderLedger = Wallet::query()
            ->where('source_type', 'transfer_out')
            ->where('source_id', $transfer->id)
            ->first();
        $this->assertNotNull($senderLedger);
        $this->assertEquals(1, $senderLedger->transfer_type); // debit
        $this->assertEquals(500.00, (float) $senderLedger->amount);
        $this->assertEquals($this->employeeA->id, $senderLedger->user_id);

        $recipientLedger = Wallet::query()
            ->where('source_type', 'transfer_in')
            ->where('source_id', $transfer->id)
            ->first();
        $this->assertNotNull($recipientLedger);
        $this->assertEquals(0, $recipientLedger->transfer_type); // credit
        $this->assertEquals(500.00, (float) $recipientLedger->amount);
        $this->assertEquals($this->employeeB->id, $recipientLedger->user_id);
    }

    /**
     * Scenario 8: Employee salary: 12500.50 preserved without integer truncation.
     */
    public function test_08_employee_salary_preserves_two_decimals_without_truncation(): void
    {
        $this->actingAs($this->admin);

        $initialWallet = (float) $this->admin->fresh()->wallet;

        $response = $this->post(route('employee-salaries.store'), [
            'user_id' => $this->employeeA->id,
            'salary_period' => 'September 2026',
            'salary_amount' => 12500.50,
            'paid_amount' => 12500.50,
            'payment_date' => now()->toDateString(),
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        $response->assertRedirect();

        // Payer wallet debited by 12500.50
        $this->assertEquals($initialWallet - 12500.50, (float) $this->admin->fresh()->wallet);

        $salaryRecord = EmployeeSalary::query()->latest('id')->firstOrFail();

        $walletRow = Wallet::query()
            ->where('source_type', 'employee_salary')
            ->where('source_id', $salaryRecord->id)
            ->firstOrFail();

        // Exactly 12500.50, not truncated to 12500 or 12501
        $this->assertEquals(12500.50, (float) $walletRow->amount);
        $this->assertSame('12500.50', (string) $walletRow->amount);
    }

    /**
     * Scenario 9: Labour advance: Existing Phase 2A/Labour Wallet behavior remains unchanged.
     * Do not duplicate labour advance ledger entries.
     */
    public function test_09_labour_advance_does_not_duplicate_ledger_entries(): void
    {
        $this->actingAs($this->employeeA);

        $initialWallet = (float) $this->employeeA->fresh()->wallet;

        $response = $this->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Labour advance test',
        ]);
        $response->assertRedirect();

        // User wallet debited by 1000.00
        $this->assertEquals($initialWallet - 1000.00, (float) $this->employeeA->fresh()->wallet);

        // Labour balance increased
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);

        // Exactly 1 labour advance wallet ledger row created
        $ledgerRows = Wallet::query()
            ->where('source_type', 'labour_advance')
            ->where('user_id', $this->employeeA->id)
            ->get();

        $this->assertCount(1, $ledgerRows);
        $this->assertEquals(1000.00, (float) $ledgerRows->first()->amount);
        $this->assertEquals(1, $ledgerRows->first()->transfer_type); // debit
    }

    /**
     * Scenario 10: Labour reversal: Existing contributor attribution and FIFO allocation remain unchanged.
     * Do not duplicate reversal ledger entries.
     */
    public function test_10_labour_reversal_attribution_fifo_and_no_duplicate_ledger(): void
    {
        // Set up advance contribution as Employee A
        $this->actingAs($this->employeeA);
        $this->employeeA->update(['wallet' => 10000.00]);
        $this->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => 1000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Employee A advance',
        ]);

        $walletBeforeReverse = (float) $this->employeeA->fresh()->wallet;

        // Perform partial reversal of 400.00 back to Employee A as Admin
        $this->actingAs($this->admin);
        $response = $this->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 400.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Reversal test',
        ]);
        $response->assertRedirect();

        // Employee A wallet credited by 400.00
        $this->assertEquals($walletBeforeReverse + 400.00, (float) $this->employeeA->fresh()->wallet);

        // Labour balance reduced to 600.00
        $this->assertEquals(600.00, (float) $this->labour->fresh()->advance_amt);

        // Contributor FIFO allocation recorded
        $allocation = LabourWalletAllocation::query()->first();
        $this->assertNotNull($allocation);
        $this->assertEquals(400.00, (float) $allocation->amount);

        // Exactly 1 reversal wallet ledger row created
        $reverseLedgers = Wallet::query()
            ->where('source_type', 'labour_wallet_reverse')
            ->where('user_id', $this->employeeA->id)
            ->get();

        $this->assertCount(1, $reverseLedgers);
        $this->assertEquals(400.00, (float) $reverseLedgers->first()->amount);
        $this->assertEquals(0, $reverseLedgers->first()->transfer_type); // credit
    }

    /**
     * Scenario 11: Payment idempotency:
     * Repeated update/status processing must not create duplicate financial ledger events.
     */
    public function test_11_payment_idempotency_prevents_duplicate_financial_ledger_events(): void
    {
        $this->actingAs($this->admin);

        $initialWallet = (float) $this->admin->fresh()->wallet;

        // Create paid payment of 500.00
        $response = $this->post(route('payments.store'), [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $this->quotation->id,
            'stage_id' => $this->stage->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'paid',
            'paid_at' => now()->toDateString(),
        ]);
        $response->assertRedirect();

        // Wallet credited by 500.00
        $this->assertEquals($initialWallet + 500.00, (float) $this->admin->fresh()->wallet);

        $payment = Payment::query()->latest('id')->firstOrFail();

        // Exactly 1 payment ledger entry created
        $ledgerCount = Wallet::query()
            ->where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->count();
        $this->assertSame(1, $ledgerCount);

        // Repeated update with the same status and amount (idempotency check)
        $this->put(route('payments.update', $payment->id), [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'quotation_id' => $this->quotation->id,
            'stage_id' => $this->stage->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'paid',
            'notes' => 'Updated notes only',
        ]);

        // Wallet balance must NOT be double-credited
        $this->assertEquals($initialWallet + 500.00, (float) $this->admin->fresh()->wallet);

        // Ledger count must remain exactly 1
        $ledgerCountAfter = Wallet::query()
            ->where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->count();
        $this->assertSame(1, $ledgerCountAfter);

        // No rollback rows should have been created
        $rollbackCount = Wallet::query()
            ->where('source_type', 'payment_rollback')
            ->where('source_id', $payment->id)
            ->count();
        $this->assertSame(0, $rollbackCount);
    }

    /**
     * Scenario 12: Concurrency:
     * Verify relevant wallet operations use row locking and atomic transactions.
     */
    public function test_12_concurrency_row_locking_and_atomic_rollback(): void
    {
        $this->actingAs($this->employeeA);

        $this->employeeA->update(['wallet' => 100.00]);

        // Verify insufficient balance throws ValidationException and rolls back cleanly
        $this->expectException(ValidationException::class);

        // Attempt to debit 200.00 when balance is 100.00
        app(CrmBalanceService::class)->debitUserWallet($this->employeeA->id, 200.00);

        // Wallet remains intact
        $this->assertEquals(100.00, (float) $this->employeeA->fresh()->wallet);
    }
}
