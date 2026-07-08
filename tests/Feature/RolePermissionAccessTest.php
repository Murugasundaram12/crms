<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolePermissionAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

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
            $table->string('status')->nullable();
            $table->decimal('wallet', 12, 2)->default(0);
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });
    }

    public function test_user_can_access_only_routes_allowed_by_assigned_role_permissions(): void
    {
        $user = User::factory()->create(['role' => 'Employee']);
        $role = Role::query()->create(['name' => 'Limited User']);
        $allowedPermission = Permission::query()->create([
            'name' => 'List Roles',
            'key' => 'roles-list',
        ]);
        Permission::query()->create([
            'name' => 'List Permissions',
            'key' => 'permissions-list',
        ]);

        $role->permissions()->sync([$allowedPermission->id]);
        $user->roles()->sync([$role->id]);

        $this->assertTrue($user->fresh()->hasPermission('roles-list'));
        $this->assertFalse($user->fresh()->hasPermission('permissions-list'));
        $this->assertTrue(Gate::forUser($user)->allows('roles-list'));
        $this->assertFalse(Gate::forUser($user)->allows('permissions-list'));

        $this->actingAs($user)
            ->get(route('roles.index'))
            ->assertOk();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('permissions.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access this module.');
    }

    public function test_rendered_ui_permission_context_contains_only_assigned_user_permissions(): void
    {
        $user = User::factory()->create(['role' => 'Employee']);
        $role = Role::query()->create(['name' => 'Role Viewer']);
        $allowedPermission = Permission::query()->create([
            'name' => 'List Roles',
            'key' => 'roles-list',
        ]);
        Permission::query()->create([
            'name' => 'List Permissions',
            'key' => 'permissions-list',
        ]);

        $role->permissions()->sync([$allowedPermission->id]);
        $user->roles()->sync([$role->id]);

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertOk();

        $context = $this->permissionContextFromResponse($response->getContent());

        $this->assertContains('roles-list', $context['userPermissions']);
        $this->assertNotContains('permissions-list', $context['userPermissions']);
    }

    public function test_user_without_required_permission_cannot_open_protected_create_route(): void
    {
        $user = User::factory()->create(['role' => 'Employee']);
        $role = Role::query()->create(['name' => 'Viewer']);
        $permission = Permission::query()->create([
            'name' => 'List Expenses',
            'key' => 'expenses-list',
        ]);

        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->get(route('excel.import.form'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access this module.');
    }

    public function test_super_admin_can_access_protected_route_without_individual_permission(): void
    {
        $user = User::factory()->create(['role' => 'Super Admin']);

        $this->assertTrue(Gate::forUser($user)->allows('permissions-list'));

        $this->actingAs($user)
            ->get(route('permissions.index'))
            ->assertOk();
    }

    public function test_super_admin_rendered_ui_permission_context_contains_all_permission_keys(): void
    {
        $user = User::factory()->create(['role' => 'Super Admin']);

        Permission::query()->create([
            'name' => 'List Permissions',
            'key' => 'permissions-list',
        ]);
        Permission::query()->create([
            'name' => 'Create Expenses',
            'key' => 'expenses-create',
        ]);

        $response = $this->actingAs($user)->get(route('permissions.index'));

        $response->assertOk();

        $context = $this->permissionContextFromResponse($response->getContent());

        $this->assertContains('permissions-list', $context['userPermissions']);
        $this->assertContains('expenses-create', $context['userPermissions']);
    }

    public function test_permissions_page_handles_permissions_without_created_date(): void
    {
        $user = User::factory()->create(['role' => 'Super Admin']);

        DB::table('permissions')->insert([
            'name' => 'List Permissions',
            'key' => 'permissions-list',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertSee('List Permissions')
            ->assertSee('>-<', false);
    }

    private function permissionContextFromResponse(string $html): array
    {
        preg_match('/window\.crmPermissionContext = (.*?);/s', $html, $matches);

        $this->assertNotEmpty($matches, 'Permission UI context was not rendered.');

        $context = json_decode(html_entity_decode($matches[1]), true);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('userPermissions', $context);
        $this->assertArrayHasKey('permissionRoutes', $context);

        return $context;
    }
}
