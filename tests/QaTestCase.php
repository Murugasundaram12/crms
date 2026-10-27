<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class QaTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // STRICT PRODUCTION SAFETY CHECK
        $this->assertNotProductionDatabase();

        // If running in sqlite testing environment, ensure tables exist
        if (config('database.default') === 'sqlite') {
            $this->ensureBasicTestSchema();
            $this->setupSqliteTestSchema();
        }
    }

    /**
     * Prevents tests from executing against the live production database.
     */
    protected function assertNotProductionDatabase(): void
    {
        $defaultConn = config('database.default');
        $dbName = config("database.connections.{$defaultConn}.database");

        if ($defaultConn === 'mysql' && in_array(strtolower((string) $dbName), ['admin_crms', 'crms'], true)) {
            throw new \RuntimeException(
                "CRITICAL SAFETY VIOLATION: Automated tests are blocked from running against the protected database ({$dbName})!"
            );
        }
    }

    protected function setupSqliteTestSchema(): void
    {
        // Use basic schema from BaseTestCase or initialize needed tables
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('phone')->nullable();
                $table->string('designation')->nullable();
                $table->string('role')->nullable();
                $table->string('address')->nullable();
                $table->decimal('hourly_rate', 10, 2)->nullable();
                $table->date('hire_date')->nullable();
                $table->string('status')->default('active');
                $table->decimal('wallet', 14, 2)->default(0);
                $table->string('avatar')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('role_id');
                $table->foreignId('permission_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table): void {
                $table->id();
                $table->string('project_code')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->string('priority')->nullable();
                $table->string('status')->default('Ongoing');
                $table->integer('progress')->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('location')->nullable();
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->decimal('profit', 14, 2)->default(0);
                $table->decimal('budget', 14, 2)->default(0);
                $table->decimal('spent', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table): void {
                $table->id();
                $table->string('quotation_number')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->timestamp('quotation_date')->nullable();
                $table->string('quotation_title')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('sub_total', 14, 2)->default(0);
                $table->decimal('gst_percent', 8, 2)->default(0);
                $table->decimal('discount_percent', 8, 2)->default(0);
                $table->string('status')->default('Pending');
                $table->integer('validity_days')->default(30);
                $table->integer('duration_days')->default(30);
                $table->timestamp('start_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->string('payment_code')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->foreignId('quotation_id')->nullable();
                $table->foreignId('stage_id')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('method')->default('cash');
                $table->string('status')->default('paid');
                $table->date('payment_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Creates a Super Admin test user with full wildcard access.
     */
    protected function createSuperAdmin(array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Super Admin'], ['description' => 'Full access']);
        
        $user = User::factory()->create(array_merge([
            'role' => 'Super Admin',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ], $attributes));

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /**
     * Creates a Manager user with specific permissions.
     */
    protected function createManagerWithPermissions(array $permissionKeys = [], array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Manager'], ['description' => 'Manager']);
        
        $permissionIds = [];
        foreach ($permissionKeys as $key) {
            $perm = Permission::query()->firstOrCreate(
                ['key' => $key],
                ['name' => ucwords(str_replace('-', ' ', $key))]
            );
            $permissionIds[] = $perm->id;
        }

        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create(array_merge([
            'role' => 'Manager',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ], $attributes));

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /**
     * Creates a standard Employee user with minimal/restricted permissions.
     */
    protected function createRestrictedEmployee(array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Employee'], ['description' => 'Employee']);
        
        $user = User::factory()->create(array_merge([
            'role' => 'Employee',
            'status' => 'active',
            'password' => Hash::make('password123'),
        ], $attributes));

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }
}
