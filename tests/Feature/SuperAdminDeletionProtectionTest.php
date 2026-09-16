<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuperAdminDeletionProtectionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $targetSuperAdmin;
    protected User $manager;
    protected User $normalEmployee;
    protected Role $superAdminRole;
    protected Role $managerRole;
    protected Role $employeeRole;
    protected Permission $deletePermission;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->superAdminRole = Role::query()->firstOrCreate(['name' => 'Super Admin']);
        $this->managerRole = Role::query()->firstOrCreate(['name' => 'Manager']);
        $this->employeeRole = Role::query()->firstOrCreate(['name' => 'Employee']);

        $this->deletePermission = Permission::query()->firstOrCreate(
            ['key' => 'employees-delete'],
            ['name' => 'Delete Employees']
        );
        $listPermission = Permission::query()->firstOrCreate(
            ['key' => 'employees-list'],
            ['name' => 'List Employees']
        );

        $this->superAdminRole->permissions()->syncWithoutDetaching([
            $this->deletePermission->id,
            $listPermission->id,
        ]);
        $this->managerRole->permissions()->syncWithoutDetaching([
            $this->deletePermission->id,
            $listPermission->id,
        ]);

        // Primary Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Primary Super Admin',
            'email' => 'primary_super_admin_' . uniqid() . '@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
        ]);
        $this->superAdmin->roles()->sync([$this->superAdminRole->id]);
        $this->superAdmin->clearResolvedPermissions();

        // Target Super Admin (to test that another Super Admin cannot be deleted by anyone)
        $this->targetSuperAdmin = User::factory()->create([
            'name' => 'Target Super Admin',
            'email' => 'target_super_admin_' . uniqid() . '@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
        ]);
        $this->targetSuperAdmin->roles()->sync([$this->superAdminRole->id]);
        $this->targetSuperAdmin->clearResolvedPermissions();

        // Manager with employees-delete permission
        $this->manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager_' . uniqid() . '@example.com',
            'role' => 'Manager',
            'status' => 'active',
        ]);
        $this->manager->roles()->sync([$this->managerRole->id]);
        $this->manager->clearResolvedPermissions();

        // Normal Employee
        $this->normalEmployee = User::factory()->create([
            'name' => 'Normal Employee',
            'email' => 'normal_employee_' . uniqid() . '@example.com',
            'role' => 'Employee',
            'status' => 'active',
        ]);
        $this->normalEmployee->roles()->sync([$this->employeeRole->id]);
        $this->normalEmployee->clearResolvedPermissions();
    }

    /**
     * Requirement a & b: Web deletion of Super Admin is rejected and record remains unchanged.
     */
    public function test_web_deletion_of_super_admin_is_rejected_via_redirect(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('employees.destroy', $this->targetSuperAdmin));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('error', 'The Super Admin account cannot be deleted.');

        // Verify Super Admin database row remains completely intact
        $this->assertDatabaseHas('users', [
            'id' => $this->targetSuperAdmin->id,
            'email' => $this->targetSuperAdmin->email,
        ]);
    }

    /**
     * Requirement a & b: AJAX / JSON deletion of Super Admin returns 422 JSON error.
     */
    public function test_ajax_deletion_of_super_admin_is_rejected_with_json_error(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('employees.destroy', $this->targetSuperAdmin));

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The Super Admin account cannot be deleted.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->targetSuperAdmin->id,
            'email' => $this->targetSuperAdmin->email,
        ]);
    }

    /**
     * Non-super admin with delete permission also cannot delete Super Admin.
     */
    public function test_manager_with_delete_permission_cannot_delete_super_admin(): void
    {
        $response = $this->actingAs($this->manager)
            ->delete(route('employees.destroy', $this->targetSuperAdmin));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('error', 'The Super Admin account cannot be deleted.');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetSuperAdmin->id,
        ]);
    }

    /**
     * Requirement c: Normal employee can still be deleted when eligible.
     */
    public function test_normal_employee_can_be_deleted_when_eligible(): void
    {
        $targetId = $this->normalEmployee->id;

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('employees.destroy', $this->normalEmployee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $targetId,
        ]);
    }

    /**
     * Normal employee deletion via JSON returns JSON success.
     */
    public function test_normal_employee_deletion_via_json_succeeds(): void
    {
        $target = User::factory()->create([
            'role' => 'Employee',
            'status' => 'active',
        ]);
        $target->roles()->sync([$this->employeeRole->id]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('employees.destroy', $target));

        $response->assertOk();
        $response->assertJson(['message' => 'User deleted successfully.']);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    /**
     * Requirement d: Model-level User::destroy() cannot delete Super Admin.
     */
    public function test_user_destroy_cannot_delete_super_admin(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Super Admin account cannot be deleted.');

        try {
            User::destroy($this->targetSuperAdmin->id);
        } finally {
            $this->assertDatabaseHas('users', [
                'id' => $this->targetSuperAdmin->id,
            ]);
        }
    }

    /**
     * Bulk User::destroy() containing Super Admin is blocked and Super Admin is not deleted.
     */
    public function test_bulk_user_destroy_containing_super_admin_is_blocked(): void
    {
        $employeeA = User::factory()->create(['role' => 'Employee']);
        $employeeA->roles()->sync([$this->employeeRole->id]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Super Admin account cannot be deleted.');

        try {
            User::destroy([$employeeA->id, $this->targetSuperAdmin->id]);
        } finally {
            $this->assertDatabaseHas('users', [
                'id' => $this->targetSuperAdmin->id,
            ]);
        }
    }

    /**
     * Requirement e: Model-level $user->delete() throws exception for Super Admin.
     */
    public function test_model_delete_throws_exception_for_super_admin(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Super Admin account cannot be deleted.');

        try {
            $this->targetSuperAdmin->delete();
        } finally {
            $this->assertDatabaseHas('users', [
                'id' => $this->targetSuperAdmin->id,
            ]);
        }
    }

    /**
     * Super Admin identified only via roles pivot is also protected at model level.
     */
    public function test_super_admin_identified_via_roles_relation_is_protected(): void
    {
        $roleSuperAdmin = User::factory()->create([
            'name' => 'Role-Only Super Admin',
            'role' => 'Employee',
            'status' => 'active',
        ]);
        $roleSuperAdmin->roles()->sync([$this->superAdminRole->id]);
        $roleSuperAdmin->clearResolvedPermissions();

        $this->assertTrue($roleSuperAdmin->isSuperAdmin());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Super Admin account cannot be deleted.');

        try {
            $roleSuperAdmin->delete();
        } finally {
            $this->assertDatabaseHas('users', [
                'id' => $roleSuperAdmin->id,
            ]);
        }
    }

    /**
     * Requirement f: Mobile API cannot deactivate/delete Super Admin.
     */
    public function test_mobile_api_cannot_delete_or_deactivate_super_admin(): void
    {
        $deviceId = 'device-test-' . uniqid();
        $plainToken = 'mobile-test-token-' . uniqid();

        \App\Models\EmployeeDevice::create([
            'employee_id' => $this->superAdmin->id,
            'device_id' => $deviceId,
            'last_seen_at' => now(),
        ]);

        $this->superAdmin->mobileApiTokens()->create([
            'name' => 'Test Token',
            'token_hash' => hash('sha256', $plainToken),
            'device_id' => $deviceId,
            'last_used_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainToken,
            'Accept' => 'application/json',
        ])->deleteJson('/api/employees/' . $this->targetSuperAdmin->id);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The Super Admin account cannot be deleted.',
        ]);

        $this->assertEquals('active', $this->targetSuperAdmin->fresh()->status);
    }

    /**
     * Requirement g: Self-delete protection still works for logged-in user.
     */
    public function test_self_delete_protection_rejects_own_account_deletion_web(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('employees.destroy', $this->superAdmin));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('error', 'You cannot delete your own user account.');

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
        ]);
    }

    /**
     * Self-delete protection in AJAX mode.
     */
    public function test_self_delete_protection_rejects_own_account_deletion_ajax(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson(route('employees.destroy', $this->superAdmin));

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot delete your own user account.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
        ]);
    }

    /**
     * Requirement h: Dependency guard behavior remains intact for normal users.
     */
    public function test_dependency_guard_prevents_deleting_employee_with_attendance(): void
    {
        Attendance::create([
            'user_id' => $this->normalEmployee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('employees.destroy', $this->normalEmployee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('error', 'User is already used in attendance and cannot be deleted.');

        $this->assertDatabaseHas('users', [
            'id' => $this->normalEmployee->id,
        ]);
    }

    /**
     * UI: Delete button is hidden for Super Admin and self, but shown for eligible normal employees.
     */
    public function test_ui_delete_button_visibility_in_employees_index(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('employees.index'));

        $response->assertOk();

        // The target Super Admin delete route should NOT appear in delete modal triggers
        $response->assertDontSee('data-delete-action="' . route('employees.destroy', $this->targetSuperAdmin) . '"', false);

        // Self delete route should NOT appear in delete modal triggers
        $response->assertDontSee('data-delete-action="' . route('employees.destroy', $this->superAdmin) . '"', false);

        // Normal employee delete route SHOULD appear
        $response->assertSee('data-delete-action="' . route('employees.destroy', $this->normalEmployee) . '"', false);
    }
}
