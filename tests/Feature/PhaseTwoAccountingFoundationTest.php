<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseUnpaidDate;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseTwoAccountingFoundationTest extends TestCase
{
    protected User $superAdmin;
    protected User $userA;
    protected User $userB;
    protected Labour $labour;
    protected Vendor $vendor;
    protected Project $projectA;
    protected Project $projectB;
    protected Project $projectC;
    protected Project $projectD;
    protected PaymentMethod $paymentMethod;
    protected MainCategory $mainCategory;
    protected Category $category;
    protected Category $salaryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->createSchema();

        $superAdminRole = Role::query()->create(['name' => 'Super Admin']);
        $employeeRole = Role::query()->create(['name' => 'Employee']);

        $permissions = [
            'expenses-list', 'expenses-create', 'expenses-edit', 'expenses-delete',
            'labour-expenses-list', 'labour-expenses-create', 'labour-expenses-edit', 'labour-expenses-delete',
            'vendor-expenses-list', 'vendor-expenses-create', 'vendor-expenses-edit', 'vendor-expenses-delete',
            'unpaid-expenses-list', 'unpaid-expenses-create',
            'labour-salaries-list', 'labour-salaries-create', 'labour-salaries-edit', 'labour-salaries-delete',
        ];

        $permIds = [];
        foreach ($permissions as $permKey) {
            $p = Permission::query()->create(['name' => $permKey, 'key' => $permKey]);
            $permIds[] = $p->id;
        }

        $superAdminRole->permissions()->sync($permIds);
        $employeeRole->permissions()->sync($permIds);

        $this->superAdmin = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'wallet' => 50000.00,
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->userA = User::query()->create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'role' => 'Employee',
            'wallet' => 10000.00,
            'password' => bcrypt('password'),
        ]);
        $this->userA->roles()->sync([$employeeRole->id]);

        $this->userB = User::query()->create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'role' => 'Employee',
            'wallet' => 10000.00,
            'password' => bcrypt('password'),
        ]);
        $this->userB->roles()->sync([$employeeRole->id]);

        $this->paymentMethod = PaymentMethod::query()->create([
            'name' => 'Cash',
            'code' => 'CASH',
            'type' => 'cash',
            'active_status' => true,
        ]);

        $client = Client::query()->create(['name' => 'Client Alpha']);

        $this->projectA = Project::query()->create([
            'name' => 'Project Alpha',
            'project_code' => 'PRJ-A',
            'client_id' => $client->id,
            'type' => 'general',
        ]);

        $this->projectB = Project::query()->create([
            'name' => 'Project Beta',
            'project_code' => 'PRJ-B',
            'client_id' => $client->id,
            'type' => 'general',
        ]);

        $this->projectC = Project::query()->create([
            'name' => 'Project Gamma',
            'project_code' => 'PRJ-C',
            'client_id' => $client->id,
            'type' => 'general',
        ]);

        $this->projectD = Project::query()->create([
            'name' => 'Project Delta',
            'project_code' => 'PRJ-D',
            'client_id' => $client->id,
            'type' => 'general',
        ]);

        $this->mainCategory = MainCategory::query()->create(['name' => 'CIVIL']);
        $this->category = Category::query()->create([
            'name' => 'Material Supplies',
            'main_category_id' => $this->mainCategory->id,
        ]);

        $this->salaryCategory = Category::query()->create([
            'name' => 'LABOUR SALARY',
            'main_category_id' => $this->mainCategory->id,
        ]);

        $labourRole = LabourRole::query()->create([
            'name' => 'Mason',
            'salary_type' => 'daily',
            'salary' => 500.00,
        ]);

        $this->labour = Labour::query()->create([
            'name' => 'Ramu',
            'phone' => '9999999991',
            'phone_number' => '9999999991',
            'labour_role_id' => $labourRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $this->vendor = Vendor::query()->create([
            'name' => 'Apex Hardware',
            'phone' => '9999999992',
            'advance_amt' => 0.00,
            'advance_amount' => 0.00,
        ]);
    }

    protected function createSchema(): void
    {
        Schema::dropIfExists('labour_assignments');
        Schema::dropIfExists('labour_attendances');
        Schema::dropIfExists('labour_salaries');
        Schema::dropIfExists('advance_history');
        Schema::dropIfExists('expenses_unpaid_date');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('wallet');
        Schema::dropIfExists('labour_roles');
        Schema::dropIfExists('labours');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('main_categories');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->nullable();
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');
            $table->boolean('active_status')->default(true);
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
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

        Schema::create('main_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_category_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('labour_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('salary_type')->default('daily');
            $table->decimal('salary', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('phone_number')->nullable();
            $table->foreignId('labour_role_id')->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('advance_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('client_id')->default(0);
            $table->unsignedBigInteger('project_id')->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->integer('payment_mode')->default(1);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->tinyInteger('transfer_type')->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('current_date')->nullable();
            $table->tinyInteger('active_status')->default(1);
            $table->tinyInteger('delete_status')->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('labour_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('main_category_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('paid_amt', 14, 2)->default(0);
            $table->decimal('unpaid_amt', 14, 2)->default(0);
            $table->decimal('extra_amt', 14, 2)->default(0);
            $table->date('current_date')->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('payment_mode')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('editedBy')->nullable();
            $table->string('reason')->nullable();
            $table->tinyInteger('is_advance')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('expenses_unpaid_date', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->unsignedBigInteger('vendor_expense_transaction_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->decimal('paid_amount', 14, 2);
            $table->date('current_date');
            $table->time('current_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('advance_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('labour_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
            $table->unsignedBigInteger('labour_salary_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('entry_type');
            $table->text('notes')->nullable();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->date('current_date');
            $table->time('current_time');
            $table->timestamps();
        });

        Schema::create('labour_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->date('salary_period_start')->nullable();
            $table->date('salary_period_end')->nullable();
            $table->decimal('salary_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('advance_adjusted', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable();
            $table->string('status')->default('paid');
            $table->boolean('advance_paid')->default(false);
            $table->timestamps();
        });

        Schema::create('labour_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->foreignId('employee_id')->nullable();
            $table->date('attendance_date');
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->foreignId('labour_salary_id')->nullable();
            $table->timestamps();
        });

        Schema::create('labour_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->foreignId('project_id');
            $table->foreignId('employee_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * A. salary 5000, no advance, cash 5000
     */
    public function test_salary_five_thousand_no_advance_cash_five_thousand(): void
    {
        $this->actingAs($this->userA);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 5000,
            'advance_paid' => '0',
            'advance_adjusted' => 0,
            'paid_amount' => 5000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Payer wallet debited 5000
        $this->assertEquals(5000.00, (float) $this->userA->fresh()->wallet);

        // Expense created with exact amounts
        $expense = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(5000.00, (float) $expense->amount);
        $this->assertEquals(5000.00, (float) $expense->paid_amt);
        $this->assertEquals(0.00, (float) $expense->unpaid_amt);
        $this->assertNull($expense->is_advance);

        // Wallet ledger debit created
        $walletTx = Wallet::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($walletTx);
        $this->assertEquals(5000.00, (float) $walletTx->amount);
        $this->assertEquals(1, $walletTx->transfer_type);
    }

    /**
     * B. salary 5000, advance 2000, cash 3000
     */
    public function test_salary_five_thousand_advance_two_thousand_cash_three_thousand(): void
    {
        $this->actingAs($this->userA);

        // Set initial advance on labour
        $this->labour->update(['advance_amt' => 2000.00]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 5000,
            'advance_paid' => '1',
            'advance_adjusted' => 2000,
            'paid_amount' => 3000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Payer wallet debited 3000 (cash paid)
        $this->assertEquals(7000.00, (float) $this->userA->fresh()->wallet);

        // Labour advance balance reduced by 2000 -> becomes 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // AdvanceHistory settle record created
        $advHist = AdvanceHistory::query()->where('entry_type', 'settle')->first();
        $this->assertNotNull($advHist);
        $this->assertEquals(2000.00, (float) $advHist->amount);

        // Expense created with amount=5000, paid_amt=5000 (cash + advance), is_advance=1
        $expense = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(5000.00, (float) $expense->amount);
        $this->assertEquals(5000.00, (float) $expense->paid_amt);
        $this->assertEquals(0.00, (float) $expense->unpaid_amt);
        $this->assertEquals(1, $expense->is_advance);

        // Wallet debit created only for cash paid (3000)
        $walletTx = Wallet::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($walletTx);
        $this->assertEquals(3000.00, (float) $walletTx->amount);
    }

    /**
     * C. salary 5000, advance 5000, cash 0
     * Critical scenario: MUST create/retain Expense amount=5000, paid_amt=5000, unpaid_amt=0, is_advance=1
     * Do NOT delete the salary expense merely because paidAmount == 0.
     */
    public function test_salary_five_thousand_advance_five_thousand_cash_zero(): void
    {
        $this->actingAs($this->userA);

        // Set initial advance on labour
        $this->labour->update(['advance_amt' => 5000.00]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 5000,
            'advance_paid' => '1',
            'advance_adjusted' => 5000,
            'paid_amount' => 0,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Payer wallet unchanged (cash paid is 0)
        $this->assertEquals(10000.00, (float) $this->userA->fresh()->wallet);

        // Labour advance balance reduced to 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // Expense MUST exist with amount=5000, paid_amt=5000, unpaid_amt=0, is_advance=1
        $expense = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($expense, 'Expense must not be deleted or omitted when cash paid is zero but advance adjusted');
        $this->assertEquals(5000.00, (float) $expense->amount);
        $this->assertEquals(5000.00, (float) $expense->paid_amt);
        $this->assertEquals(0.00, (float) $expense->unpaid_amt);
        $this->assertEquals(1, $expense->is_advance);

        // No cash wallet debit ledger row created
        $walletTx = Wallet::query()->where('source_type', 'labour_salary')->first();
        $this->assertNull($walletTx);
    }

    /**
     * D. salary update after advance adjustment
     */
    public function test_salary_update_after_advance_adjustment(): void
    {
        $this->actingAs($this->userA);

        $this->labour->update(['advance_amt' => 5000.00]);

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 5000,
            'advance_paid' => '1',
            'advance_adjusted' => 2000,
            'paid_amount' => 3000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salary = LabourSalary::query()->first();
        $this->assertNotNull($salary);
        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);

        // Update salary to 6000 earned, 3000 advance, 3000 cash
        $response = $this->put(route('labour-salaries.update', $salary->id), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 6000,
            'advance_paid' => '1',
            'advance_adjusted' => 3000,
            'paid_amount' => 3000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
            'status' => 'paid',
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Advance reduced by additional 1000 -> becomes 2000
        $this->assertEquals(2000.00, (float) $this->labour->fresh()->advance_amt);

        // Expense updated
        $expense = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertEquals(6000.00, (float) $expense->amount);
        $this->assertEquals(6000.00, (float) $expense->paid_amt);
        $this->assertEquals(0.00, (float) $expense->unpaid_amt);
        $this->assertEquals(1, $expense->is_advance);
    }

    /**
     * E. salary deletion restores advance correctly
     */
    public function test_salary_deletion_restores_advance_correctly(): void
    {
        $this->actingAs($this->userA);

        $this->labour->update(['advance_amt' => 3000.00]);

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 5000,
            'advance_paid' => '1',
            'advance_adjusted' => 2000,
            'paid_amount' => 3000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salary = LabourSalary::query()->first();
        $this->assertEquals(1000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals(7000.00, (float) $this->userA->fresh()->wallet);

        // Delete the salary
        $response = $this->delete(route('labour-salaries.destroy', $salary->id));
        $response->assertRedirect(route('labour-salaries.index'));

        // Advance restored to 3000.00
        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);

        // Payer wallet refunded cash paid (3000.00) back to 10000.00
        $this->assertEquals(10000.00, (float) $this->userA->fresh()->wallet);

        // Expense deleted (soft deleted via model)
        $this->assertSoftDeleted('expenses', ['source_type' => 'labour_salary', 'source_id' => $salary->id]);
        $this->assertNull(Expense::query()->where('source_type', 'labour_salary')->where('source_id', $salary->id)->first());

        // Advance history records for salary deleted
        $this->assertDatabaseMissing('advance_history', ['labour_salary_id' => $salary->id]);
    }

    /**
     * F. single-project salary gets project_id
     */
    public function test_single_project_salary_gets_project_id(): void
    {
        $this->actingAs($this->userA);

        // Create assignment for Project A (2026-09-01 to 2026-09-10)
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectA->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Create 2 attendances on Project A dates (Tuesday 2026-09-01, Wednesday 2026-09-02)
        $att1 = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);
        $att2 = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 1000,
            'advance_paid' => '0',
            'advance_adjusted' => 0,
            'paid_amount' => 1000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => [$att1->id, $att2->id],
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        $expense = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->projectA->id, $expense->project_id);
    }

    /**
     * G. multi-project salary follows deterministic predominant-project rule
     */
    public function test_multi_project_salary_follows_deterministic_predominant_project_rule(): void
    {
        $this->actingAs($this->userA);

        // Subtest 1: Project A has 2 attendances, Project B has 1 -> Project A wins
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectA->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'status' => 'active',
        ]);
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectB->id,
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-04',
            'status' => 'active',
        ]);

        $att1 = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);
        $att2 = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);
        $att3 = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-03',
            'status' => 'present',
        ]);

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-05',
            'salary_amount' => 1500,
            'advance_paid' => '0',
            'advance_adjusted' => 0,
            'paid_amount' => 1500,
            'payment_date' => '2026-09-05',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => [$att1->id, $att2->id, $att3->id],
        ]);

        $expense1 = Expense::query()->where('source_type', 'labour_salary')->first();
        $this->assertEquals($this->projectA->id, $expense1->project_id, 'Project A should win with 2 attendances vs Project B with 1');

        // Subtest 2: Tied attendance count (1 each). Project A on 2026-09-08, Project B on 2026-09-09.
        // Latest attendance date is 2026-09-09 (Project B) -> Project B wins tie-break.
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectA->id,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-08',
            'status' => 'active',
        ]);
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectB->id,
            'start_date' => '2026-09-09',
            'end_date' => '2026-09-09',
            'status' => 'active',
        ]);

        $attTiedA = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-08',
            'status' => 'present',
        ]);
        $attTiedB = LabourAttendance::query()->create([
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-09',
            'status' => 'present',
        ]);

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-08',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 1000,
            'advance_paid' => '0',
            'advance_adjusted' => 0,
            'paid_amount' => 1000,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => [$attTiedA->id, $attTiedB->id],
        ]);

        $salaries = LabourSalary::query()->orderBy('id')->get();
        $expense2 = Expense::query()->where('source_type', 'labour_salary')->where('source_id', $salaries[1]->id)->first();
        $this->assertEquals($this->projectB->id, $expense2->project_id, 'Project B should win tie-break due to latest attendance date (2026-09-09 > 2026-09-08)');

        // Subtest 3: Fallback when no attendances linked -> assignment overlapping salary period (Project C)
        LabourAssignment::query()->create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->projectC->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-20',
            'status' => 'active',
        ]);

        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-16',
            'salary_period_end' => '2026-09-18',
            'salary_amount' => 1000,
            'advance_paid' => '0',
            'advance_adjusted' => 0,
            'paid_amount' => 1000,
            'payment_date' => '2026-09-18',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $salaries = LabourSalary::query()->orderBy('id')->get();
        $expense3 = Expense::query()->where('source_type', 'labour_salary')->where('source_id', $salaries[2]->id)->first();
        $this->assertEquals($this->projectC->id, $expense3->project_id, 'Should resolve to Project C via overlapping assignment');
    }

    /**
     * H. labour expense edit preserves original owner
     */
    public function test_labour_expense_edit_preserves_original_owner(): void
    {
        // User A creates expense
        $this->actingAs($this->userA);

        $response = $this->post(route('labour-expenses.store'), [
            'labour_id' => $this->labour->id,
            'category_id' => $this->category->id,
            'amount' => 100.00,
            'paid_amount' => 100.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => '2026-09-10',
        ]);
        $response->assertRedirect();

        $expense = Expense::query()->whereNotNull('labour_id')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->userA->id, $expense->user_id);
        $this->assertEquals(9900.00, (float) $this->userA->fresh()->wallet);

        // User B edits the expense (amount & paid_amount increased to 150.00)
        $this->actingAs($this->userB);

        $updateResponse = $this->put(route('labour-expenses.update.legacy', $expense->id), [
            'labour_id' => $this->labour->id,
            'category_id' => $this->category->id,
            'amount' => 150.00,
            'paid_amount' => 150.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => '2026-09-10',
        ]);
        $updateResponse->assertRedirect();

        $expense = $expense->fresh();

        // 1. Expense user_id remains User A
        $this->assertEquals($this->userA->id, $expense->user_id);

        // 2. editedBy is User B
        $this->assertEquals($this->userB->id, $expense->editedBy);

        // 3. User B's wallet must NOT be debited
        $this->assertEquals(10000.00, (float) $this->userB->fresh()->wallet);

        // 4. User A's wallet is debited by the difference (150 - 100 = 50)
        $this->assertEquals(9850.00, (float) $this->userA->fresh()->wallet);
    }

    /**
     * I. vendor expense edit preserves original owner
     */
    public function test_vendor_expense_edit_preserves_original_owner(): void
    {
        // User A creates vendor expense
        $this->actingAs($this->userA);

        $response = $this->post(route('vendor-expenses.store'), [
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'amount' => 200.00,
            'paid_amount' => 200.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => '2026-09-10',
        ]);
        $response->assertRedirect();

        $expense = Expense::query()->whereNotNull('vendor_id')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->userA->id, $expense->user_id);
        $this->assertEquals(9800.00, (float) $this->userA->fresh()->wallet);

        // User B edits the expense (paid_amount increased to 300.00)
        $this->actingAs($this->userB);

        $updateResponse = $this->put(route('vendor-expenses.update.legacy', $expense->id), [
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'amount' => 300.00,
            'paid_amount' => 300.00,
            'payment_method_id' => $this->paymentMethod->id,
            'current_date' => '2026-09-10',
        ]);
        $updateResponse->assertRedirect();

        $expense = $expense->fresh();

        // 1. Expense user_id remains User A
        $this->assertEquals($this->userA->id, $expense->user_id);

        // 2. editedBy is User B
        $this->assertEquals($this->userB->id, $expense->editedBy);

        // 3. User B's wallet must NOT be debited
        $this->assertEquals(10000.00, (float) $this->userB->fresh()->wallet);

        // 4. User A's wallet is debited by the difference (300 - 200 = 100)
        $this->assertEquals(9700.00, (float) $this->userA->fresh()->wallet);
    }

    /**
     * J. decimal expense amount survives update/refund
     * Test: ₹150.50 must remain ₹150.50 through update/refund.
     */
    public function test_decimal_expense_amount_survives_update_and_refund(): void
    {
        $this->actingAs($this->userA);

        // Store expense with 150.50
        $response = $this->post(route('expenses.store.new'), [
            'category_id' => $this->category->id,
            'amount' => 150.50,
            'paid_amt' => 150.50,
            'current_date' => '2026-09-10',
        ]);
        $response->assertRedirect();

        $expense = Expense::query()->whereNull('labour_id')->whereNull('vendor_id')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(150.50, (float) $expense->amount);
        $this->assertEquals(150.50, (float) $expense->paid_amt);
        $this->assertEquals(9849.50, (float) $this->userA->fresh()->wallet);

        // Update expense to 250.75
        $updateResponse = $this->put(route('expenses.update.new', $expense->id), [
            'category_id' => $this->category->id,
            'amount' => 250.75,
            'paid_amt' => 250.75,
            'current_date' => '2026-09-10',
        ]);
        $updateResponse->assertRedirect();

        $expense = $expense->fresh();
        $this->assertEquals(250.75, (float) $expense->amount);
        $this->assertEquals(250.75, (float) $expense->paid_amt);
        $this->assertEquals(9749.25, (float) $this->userA->fresh()->wallet);

        // Delete (refund) expense
        $deleteResponse = $this->post(route('expenses.delete-record'), [
            'expense_id' => $expense->id,
            'delete_reason' => 'Test refund',
        ]);
        $deleteResponse->assertRedirect();

        // User wallet refunded exactly by 250.75 back to initial 10000.00
        $this->assertEquals(10000.00, (float) $this->userA->fresh()->wallet);
    }

    /**
     * K. decimal unpaid settlement survives
     * Test: ₹99.75 unpaid settlement must remain ₹99.75.
     */
    public function test_decimal_unpaid_settlement_survives(): void
    {
        $this->actingAs($this->userA);

        // Create expense with 200.00 amount, 100.25 paid, unpaid = 99.75
        $this->post(route('expenses.store.new'), [
            'category_id' => $this->category->id,
            'amount' => 200.00,
            'paid_amt' => 100.25,
            'current_date' => '2026-09-10',
        ]);

        $expense = Expense::query()->whereNull('labour_id')->whereNull('vendor_id')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(200.00, (float) $expense->amount);
        $this->assertEquals(100.25, (float) $expense->paid_amt);
        $this->assertEquals(99.75, (float) $expense->unpaid_amt);
        $this->assertEquals(9899.75, (float) $this->userA->fresh()->wallet);

        // Settle exact 99.75 unpaid amount
        $settleResponse = $this->post(route('expenses.unpaid-store'), [
            'expense_id' => $expense->id,
            'paid_amount' => 99.75,
            'notes' => 'Settling remaining 99.75',
        ]);
        $settleResponse->assertRedirect();

        $expense = $expense->fresh();
        $this->assertEquals(200.00, (float) $expense->paid_amt);
        $this->assertEquals(0.00, (float) $expense->unpaid_amt);

        // User wallet debited by 99.75 -> 9899.75 - 99.75 = 9800.00
        $this->assertEquals(9800.00, (float) $this->userA->fresh()->wallet);

        // ExpenseUnpaidDate record created with 99.75
        $unpaidLog = ExpenseUnpaidDate::query()->where('expense_id', $expense->id)->first();
        $this->assertNotNull($unpaidLog);
        $this->assertEquals(99.75, (float) $unpaidLog->paid_amount);
    }

    /**
     * L. decimal wallet transaction survives
     */
    public function test_decimal_wallet_transaction_survives(): void
    {
        $this->actingAs($this->userA);

        // Use CrmBalanceService to record wallet transaction with decimals (e.g. 125.45)
        app(CrmBalanceService::class)->recordWalletTransaction(
            $this->userA->id,
            125.45,
            'debit',
            'test_decimal',
            1,
            $this->paymentMethod->id,
            'Decimal test payment'
        );

        $this->assertEquals(9874.55, (float) $this->userA->fresh()->wallet);

        $walletRow = DB::table('wallet')->where('source_type', 'test_decimal')->first();
        $this->assertNotNull($walletRow);
        $this->assertEquals(125.45, (float) $walletRow->amount);

        $wallet = Wallet::query()->where('source_type', 'test_decimal')->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(125.45, (float) $wallet->getRawOriginal('amount'));
    }
}
