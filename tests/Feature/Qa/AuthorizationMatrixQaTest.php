<?php

namespace Tests\Feature\Qa;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class AuthorizationMatrixQaTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('user_roles');
            Schema::dropIfExists('model_has_permissions');
            Schema::dropIfExists('role_permission');
            Schema::dropIfExists('permissions');
            Schema::dropIfExists('roles');
            Schema::dropIfExists('users');

            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('role')->nullable();
                $table->string('status')->default('active');
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
                $table->foreignId('role_id');
                $table->foreignId('permission_id');
                $table->timestamps();
            });

            Schema::create('user_roles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->timestamps();
            });

            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }
    }

    /**
     * Test: Super Admin can access all protected module list pages.
     */
    public function test_super_admin_bypasses_individual_permissions_for_all_modules(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $routesToVerify = [
            route('dashboard'),
            route('clients.index'),
            route('projects.index'),
            route('tasks.index'),
            route('vendors.index'),
            route('labours.index'),
            route('labour_roles.index'),
            route('main_categories.index'),
            route('categories.index'),
            route('units.index'),
            route('payment-stages.index'),
            route('payment-methods.index'),
            route('roles.index'),
            route('permissions.index'),
        ];

        foreach ($routesToVerify as $route) {
            $response = $this->actingAs($superAdmin)->get($route);
            // Must not redirect with permission error
            $response->assertSessionMissing('error');
            $this->assertNotEquals(302, $response->getStatusCode(), "Super admin was unexpectedly redirected from {$route}");
        }
    }

    /**
     * Test: Manager can access modules allowed by their assigned permissions.
     */
    public function test_manager_can_access_allowed_modules(): void
    {
        $manager = $this->createManagerWithPermissions([
            'clients-list',
            'projects-list',
            'tasks-list',
        ]);

        $this->actingAs($manager)->get(route('clients.index'))->assertOk();
        $this->actingAs($manager)->get(route('projects.index'))->assertOk();
        $this->actingAs($manager)->get(route('tasks.index'))->assertOk();
    }

    /**
     * Test: Manager is blocked from accessing unassigned modules (e.g. Roles/Permissions)
     * and redirected with permission error message.
     */
    public function test_manager_cannot_access_unpermitted_modules(): void
    {
        $manager = $this->createManagerWithPermissions([
            'clients-list',
        ]);

        $response = $this->actingAs($manager)
            ->from(route('dashboard'))
            ->get(route('roles.index'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to access this module.');
    }

    /**
     * Test: Employee without create permission cannot open create forms.
     */
    public function test_employee_cannot_access_create_routes(): void
    {
        $employee = $this->createRestrictedEmployee();

        $createRoutes = [
            route('clients.create'),
            route('vendors.create'),
            route('labours.create'),
            route('roles.create'),
            route('permissions.create'),
        ];

        foreach ($createRoutes as $route) {
            $response = $this->actingAs($employee)
                ->from(route('dashboard'))
                ->get($route);

            $this->assertEquals(302, $response->getStatusCode(), "Route {$route} returned {$response->getStatusCode()} instead of 302 redirect");
            $response->assertSessionHas('error', 'You do not have permission to access this module.');
        }
    }

    public function test_projects_create_route_resolves_before_show_parameter(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $response = $this->actingAs($superAdmin)->get(route('projects.create'));

        $response->assertRedirect(route('projects.index', ['create' => 1]));
    }

    public function test_tasks_create_route_resolves_before_show_parameter(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $response = $this->actingAs($superAdmin)->get(route('tasks.create'));

        $response->assertRedirect(route('tasks.index'));
    }
}
