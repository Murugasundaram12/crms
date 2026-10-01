<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class QaTestCase extends BaseTestCase
{
    use DatabaseTransactions;
    protected function setUp(): void
    {
        parent::setUp();

        // STRICT PRODUCTION SAFETY CHECK
        $this->assertNotProductionDatabase();

        // If running in sqlite testing environment, ensure tables exist
        if (config('database.default') === 'sqlite') {
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

        if ($defaultConn === 'mysql' && $dbName === 'admin_crms') {
            throw new \RuntimeException(
                'CRITICAL SAFETY VIOLATION: Automated tests are blocked from running against the live production database (admin_crms)!'
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
