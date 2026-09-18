<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourWalletAllocation;
use App\Models\LabourWalletTransaction;
use App\Models\MainCategory;
use App\Models\MobileApiToken;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseOneCriticalFixesTest extends TestCase
{
    protected User $superAdmin;
    protected User $employeeA;
    protected User $employeeB;
    protected User $unauthorizedUser;
    protected Labour $labour;
    protected PaymentMethod $paymentMethod;
    protected Project $project;
    protected Category $category;
    protected MainCategory $mainCategory;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->createSchema();

        $superAdminRole = Role::query()->create(['name' => 'Super Admin']);
        $employeeRole = Role::query()->create(['name' => 'Employee']);

        $permEdit = Permission::query()->create(['name' => 'Edit Expenses', 'key' => 'expenses-edit']);
        $permDelete = Permission::query()->create(['name' => 'Delete Expenses', 'key' => 'expenses-delete']);
        $permList = Permission::query()->create(['name' => 'List Expenses', 'key' => 'expenses-list']);

        $employeeRole->permissions()->sync([$permEdit->id, $permDelete->id, $permList->id]);
        $superAdminRole->permissions()->sync([$permEdit->id, $permDelete->id, $permList->id]);

        $this->superAdmin = User::query()->create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@example.com',
            'role' => 'Super Admin',
            'wallet' => 50000.00,
            'password' => Hash::make('password'),
        ]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->employeeA = User::query()->create([
            'name' => 'Employee A',
            'email' => 'employeeA@example.com',
            'role' => 'Employee',
            'wallet' => 20000.00,
            'password' => Hash::make('password'),
        ]);
        $this->employeeA->roles()->sync([$employeeRole->id]);

        $this->employeeB = User::query()->create([
            'name' => 'Employee B',
            'email' => 'employeeB@example.com',
            'role' => 'Employee',
            'wallet' => 20000.00,
            'password' => Hash::make('password'),
        ]);
        $this->employeeB->roles()->sync([$employeeRole->id]);

        $this->unauthorizedUser = User::query()->create([
            'name' => 'Unauthorized User',
            'email' => 'unauth@example.com',
            'role' => 'Viewer',
            'wallet' => 5000.00,
            'password' => Hash::make('password'),
        ]);

        $labourRole = LabourRole::query()->create([
            'name' => 'Carpenter',
            'salary_type' => 'daily',
            'salary' => 600.00,
        ]);

        $this->labour = Labour::query()->create([
            'name' => 'Ramesh Labour',
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $labourRole->id,
            'salary' => 600.00,
            'advance_amt' => 0.00,
        ]);

        $this->paymentMethod = PaymentMethod::query()->create([
            'name' => 'Cash',
            'code' => 'CASH',
            'type' => 'cash',
            'active_status' => true,
        ]);

        $client = Client::query()->create(['name' => 'Test Client']);
        $this->project = Project::query()->create([
            'name' => 'Test Project',
            'project_code' => 'PRJ-001',
            'client_id' => $client->id,
            'type' => 'construction',
        ]);

        $this->mainCategory = MainCategory::query()->create(['name' => 'Site Operations']);
        $this->category = Category::query()->create([
            'name' => 'Site Supplies',
            'main_category_id' => $this->mainCategory->id,
        ]);
    }

    protected function createSchema(): void
    {
        Schema::dropIfExists('labour_wallet_allocations');
        Schema::dropIfExists('labour_wallet_transactions');
        Schema::dropIfExists('advance_history');
        Schema::dropIfExists('wallet');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('mobile_api_tokens');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('labours');
        Schema::dropIfExists('labour_roles');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('main_categories');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('clients');
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

        Schema::create('mobile_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->default('mobile');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');
            $table->boolean('active_status')->default(true);
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

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('labour_id')->nullable();
            $table->foreignId('main_category_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('paid_amt', 14, 2)->default(0);
            $table->decimal('unpaid_amt', 14, 2)->default(0);
            $table->decimal('extra_amt', 14, 2)->default(0);
            $table->date('current_date')->nullable();
            $table->string('payment_mode')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('editedBy')->nullable();
            $table->tinyInteger('is_advance')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('client_id')->default(0);
            $table->unsignedBigInteger('project_id')->default(0);
            $table->integer('amount')->default(0);
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

        Schema::create('advance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('entry_type');
            $table->text('notes')->nullable();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->date('current_date');
            $table->time('current_time');
            $table->timestamps();
        });

        Schema::create('labour_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id');
            $table->foreignId('employee_id');
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->date('current_date');
            $table->time('current_time');
            $table->timestamps();
        });

        Schema::create('labour_wallet_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reversal_transaction_id');
            $table->foreignId('credit_transaction_id');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    protected function addAdvanceCredit(User $employee, float $amount): void
    {
        $this->actingAs($employee)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'credit',
            'amount' => $amount,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Credit contribution from ' . $employee->name,
        ])->assertRedirect();
    }

    protected function authBearerHeaders(User $user): array
    {
        $plain = 'tok_' . $user->id . '_' . uniqid();
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'test-device',
            'token_hash' => hash('sha256', $plain),
        ]);

        return [
            'Authorization' => 'Bearer ' . $plain,
            'Accept' => 'application/json',
        ];
    }

    // =========================================================================
    // LABOUR WALLET TESTS (CRITICAL #1 & #2)
    // =========================================================================

    /** 1. contributor can reverse own available contribution */
    public function test_contributor_can_reverse_own_available_contribution(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);
        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);

        $initialWallet = (float) $this->employeeA->fresh()->wallet;

        $response = $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Self reversal of 2000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialWallet + 2000.00, (float) $this->employeeA->fresh()->wallet);
    }

    /** 2. non-contributor cannot reverse another contributor's funds */
    public function test_non_contributor_cannot_reverse_another_contributors_funds(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);

        // Employee B attempts to reverse Employee A's contribution
        $response = $this->actingAs($this->employeeB)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals(20000.00, (float) $this->employeeB->fresh()->wallet);
    }

    /** 3. reversal > available contribution is rejected */
    public function test_reversal_exceeding_available_contribution_is_rejected(): void
    {
        $this->addAdvanceCredit($this->employeeA, 3000.00);

        $response = $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 4000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);
    }

    /** 4. reversal > current labour balance is rejected */
    public function test_reversal_exceeding_current_labour_balance_is_rejected(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);

        // Manually decrease labour advance balance (e.g. advance was settled elsewhere)
        $this->labour->update(['advance_amt' => 1500.00]);

        $response = $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(1500.00, (float) $this->labour->fresh()->advance_amt);
    }

    /** 5. partial reversal works */
    public function test_partial_reversal_works(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);

        // First partial reversal: 2000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);

        // Second partial reversal: 3000 (completing the full 5000)
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // Third reversal attempt must now fail since available balance is 0
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertSessionHasErrors(['amount']);
    }

    /** 6. multiple contributor transactions work */
    public function test_multiple_contributor_transactions_work(): void
    {
        $this->addAdvanceCredit($this->employeeA, 4000.00);
        $this->addAdvanceCredit($this->employeeB, 3000.00);

        $this->assertEquals(7000.00, (float) $this->labour->fresh()->advance_amt);

        // Employee A reverses 2500 (remaining: 1500)
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2500.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        // Employee B reverses 3000 (remaining: 0)
        $this->actingAs($this->employeeB)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeB->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        $this->assertEquals(1500.00, (float) $this->labour->fresh()->advance_amt);

        // Employee B cannot reverse further
        $this->actingAs($this->employeeB)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeB->id,
            'amount' => 500.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertSessionHasErrors(['employee_id']);
    }

    /** 7. legacy withdraw cannot credit Auth::id() */
    public function test_legacy_withdraw_cannot_credit_auth_id(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);
        $initialWallet = (float) $this->employeeB->fresh()->wallet;

        // Employee B attempts to withdraw labour advance directly into own wallet
        $response = $this->actingAs($this->employeeB)->post(route('labour-expenses.advance-store'), [
            'labour_id' => $this->labour->id,
            'entry_type' => 'withdraw',
            'amount' => 2000.00,
        ]);

        $response->assertSessionHasErrors(['entry_type']);
        $this->assertEquals(5000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialWallet, (float) $this->employeeB->fresh()->wallet);
        $this->assertDatabaseMissing('advance_history', [
            'labour_id' => $this->labour->id,
            'entry_type' => 'withdraw',
        ]);
    }

    /** 8. successful reversal creates allocation */
    public function test_successful_reversal_creates_allocation(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);

        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('labour_wallet_allocations', [
            'amount' => 2000.00,
        ]);
    }

    /** 9. successful reversal creates reversal transaction */
    public function test_successful_reversal_creates_reversal_transaction(): void
    {
        $this->addAdvanceCredit($this->employeeA, 5000.00);

        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 2000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('labour_wallet_transactions', [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'type' => 'reverse',
            'amount' => 2000.00,
        ]);
    }

    /** 10. concurrent reversal cannot double-spend */
    public function test_concurrent_or_repeated_reversal_cannot_double_spend(): void
    {
        $this->addAdvanceCredit($this->employeeA, 3000.00);

        // First reversal drains 3000
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertRedirect();

        // Immediate subsequent reversal request with same parameters
        $res = $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 3000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $res->assertSessionHasErrors(['amount']);
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);
    }

    /** 11. failed reversal rolls back all wallet/balance changes */
    public function test_failed_reversal_rolls_back_all_wallet_and_balance_changes(): void
    {
        $this->addAdvanceCredit($this->employeeA, 3000.00);
        $initialWallet = (float) $this->employeeA->fresh()->wallet;

        // Attempt reversal exceeding attributable contribution
        $this->actingAs($this->employeeA)->post(route('labour-expenses.advance-reverse'), [
            'labour_id' => $this->labour->id,
            'employee_id' => $this->employeeA->id,
            'amount' => 5000.00,
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertSessionHasErrors(['amount']);

        $this->assertEquals(3000.00, (float) $this->labour->fresh()->advance_amt);
        $this->assertEquals($initialWallet, (float) $this->employeeA->fresh()->wallet);
        $this->assertDatabaseMissing('labour_wallet_transactions', [
            'labour_id' => $this->labour->id,
            'type' => 'reverse',
        ]);
        $this->assertDatabaseMissing('labour_wallet_allocations', []);
    }

    // =========================================================================
    // MOBILE EXPENSE IDOR TESTS (CRITICAL #3)
    // =========================================================================

    /** 12. user can update own expense */
    public function test_user_can_update_own_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
            'description' => 'Original expense',
        ]);

        $headers = $this->authBearerHeaders($this->employeeA);

        $response = $this->withHeaders($headers)->putJson('/api/expenses/' . $expense->id, [
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'amount' => 1500,
            'paid_amt' => 1500,
            'current_date' => now()->toDateString(),
            'description' => 'Updated by owner',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Expense updated successfully.');
        $this->assertEquals(1500, (int) $expense->fresh()->amount);
    }

    /** 13. user cannot update another user's expense */
    public function test_user_cannot_update_another_users_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
            'description' => 'Employee A expense',
        ]);

        $headers = $this->authBearerHeaders($this->employeeB);

        // Employee B attempts to edit Employee A's expense
        $response = $this->withHeaders($headers)->putJson('/api/expenses/' . $expense->id, [
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'amount' => 2000,
            'paid_amt' => 2000,
            'current_date' => now()->toDateString(),
            'description' => 'Hacked by Employee B',
        ]);

        $response->assertForbidden();
        $this->assertEquals(1000, (int) $expense->fresh()->amount);
        $this->assertEquals('Employee A expense', $expense->fresh()->description);
    }

    /** 14. user can delete own expense */
    public function test_user_can_delete_own_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
        ]);

        $headers = $this->authBearerHeaders($this->employeeA);

        $response = $this->withHeaders($headers)->deleteJson('/api/expenses/' . $expense->id);

        $response->assertOk();
        $response->assertJsonPath('message', 'Expense deleted successfully.');
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    /** 15. user cannot delete another user's expense */
    public function test_user_cannot_delete_another_users_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
        ]);

        $headers = $this->authBearerHeaders($this->employeeB);

        // Employee B attempts to delete Employee A's expense
        $response = $this->withHeaders($headers)->deleteJson('/api/expenses/' . $expense->id);

        $response->assertForbidden();
        $this->assertNotSoftDeleted('expenses', ['id' => $expense->id]);
    }

    /** 16. authorized administrator can update another user's expense */
    public function test_authorized_administrator_can_update_another_users_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
            'description' => 'Employee A expense',
        ]);

        $adminHeaders = $this->authBearerHeaders($this->superAdmin);

        $response = $this->withHeaders($adminHeaders)->putJson('/api/expenses/' . $expense->id, [
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'amount' => 1200,
            'paid_amt' => 1200,
            'current_date' => now()->toDateString(),
            'description' => 'Admin updated',
        ]);

        $response->assertOk();
        $this->assertEquals(1200, (int) $expense->fresh()->amount);
    }

    /** 17. authorized administrator can delete another user's expense */
    public function test_authorized_administrator_can_delete_another_users_expense(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->employeeA->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
        ]);

        $adminHeaders = $this->authBearerHeaders($this->superAdmin);

        $response = $this->withHeaders($adminHeaders)->deleteJson('/api/expenses/' . $expense->id);

        $response->assertOk();
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    /** 18. unauthorized user cannot perform update/delete */
    public function test_unauthorized_user_cannot_perform_update_or_delete(): void
    {
        $expense = Expense::query()->create([
            'user_id' => $this->unauthorizedUser->id,
            'amount' => 1000,
            'paid_amt' => 1000,
            'unpaid_amt' => 0,
            'category_id' => $this->category->id,
            'main_category_id' => $this->mainCategory->id,
            'project_id' => $this->project->id,
            'current_date' => now()->toDateString(),
        ]);

        $headers = $this->authBearerHeaders($this->unauthorizedUser);

        // Attempt update without expenses-edit
        $putRes = $this->withHeaders($headers)->putJson('/api/expenses/' . $expense->id, [
            'category_id' => $this->category->id,
            'amount' => 1500,
            'paid_amt' => 1500,
            'current_date' => now()->toDateString(),
        ]);
        $putRes->assertForbidden();

        // Attempt delete without expenses-delete
        $delRes = $this->withHeaders($headers)->deleteJson('/api/expenses/' . $expense->id);
        $delRes->assertForbidden();

        // Unauthenticated update attempt
        $this->flushHeaders();
        $unauthPut = $this->putJson('/api/expenses/' . $expense->id, [
            'category_id' => $this->category->id,
            'amount' => 1500,
            'paid_amt' => 1500,
            'current_date' => now()->toDateString(),
        ]);
        $unauthPut->assertUnauthorized();

        // Unauthenticated delete attempt
        $unauthDel = $this->deleteJson('/api/expenses/' . $expense->id);
        $unauthDel->assertUnauthorized();
    }
}
