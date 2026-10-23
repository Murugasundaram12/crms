<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeIdentityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmployeeIdentityArchitectureTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Role $adminRole;
    private Role $engineerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->engineerRole = Role::firstOrCreate(['name' => 'Site Engineer']);

        $permList = [
            'employees-list',
            'employees-create',
            'employees-edit',
            'employees-delete',
        ];

        $permissionIds = [];
        foreach ($permList as $permKey) {
            $permission = Permission::firstOrCreate(['key' => $permKey], ['name' => $permKey]);
            $permissionIds[] = $permission->id;
        }

        $this->adminRole->permissions()->syncWithoutDetaching($permissionIds);

        $this->admin = User::factory()->create([
            'role' => 'Super Admin',
            'is_employee' => true,
        ]);
        $this->admin->roles()->sync([$this->adminRole->id]);
    }

    public function test_user_model_has_is_employee_flag_and_scope(): void
    {
        $employeeUser = User::factory()->create([
            'role' => 'Site Engineer',
            'is_employee' => true,
        ]);

        $clientUser = User::factory()->create([
            'role' => 'Client',
            'is_employee' => false,
        ]);

        $this->assertTrue($employeeUser->isEmployee());
        $this->assertFalse($clientUser->isEmployee());

        $employees = User::employees()->pluck('id');
        $this->assertTrue($employees->contains($employeeUser->id));
        $this->assertFalse($employees->contains($clientUser->id));
    }

    public function test_employee_identity_service_resolves_canonical_user_from_both_models(): void
    {
        $service = app(EmployeeIdentityService::class);

        $user = User::factory()->create([
            'name' => 'Alex Engineer',
            'email' => 'alex@example.com',
            'role' => 'Site Engineer',
            'is_employee' => true,
        ]);

        $employee = $service->syncEmployeeRecordForUser($user);

        $this->assertNotNull($employee);
        $this->assertSame($user->id, $employee->user_id);

        // Resolve user from Employee model
        $resolvedFromModel = $service->resolveUser($employee);
        $this->assertNotNull($resolvedFromModel);
        $this->assertSame($user->id, $resolvedFromModel->id);

        // Resolve an Employee ID explicitly, then resolve its canonical User.
        $resolvedEmployee = $service->resolveEmployeeById($employee->id);
        $resolvedFromEmpId = $resolvedEmployee ? $service->resolveUser($resolvedEmployee) : null;
        $this->assertNotNull($resolvedFromEmpId);
        $this->assertSame($user->id, $resolvedFromEmpId->id);

        // Resolve a User ID explicitly.
        $resolvedFromUserId = $service->resolveUserById($user->id);
        $this->assertNotNull($resolvedFromUserId);
        $this->assertSame($user->id, $resolvedFromUserId->id);

        // Resolve employee from User model
        $resolvedEmpFromUser = $service->resolveEmployee($user);
        $this->assertNotNull($resolvedEmpFromUser);
        $this->assertSame($employee->id, $resolvedEmpFromUser->id);
    }

    public function test_bare_numeric_identity_is_not_guessed_as_a_user_or_employee_id(): void
    {
        $service = app(EmployeeIdentityService::class);
        $employee = Employee::create([
            'name' => 'Numeric Collision Employee',
            'email' => 'numeric-collision@example.com',
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => null,
        ]);

        $this->assertNull($service->resolveUser($employee->id));
        $this->assertNull($service->resolveEmployee($employee->id));
    }

    public function test_canonical_employee_user_mapping_takes_precedence_over_email_fallback(): void
    {
        $service = app(EmployeeIdentityService::class);
        $canonicalUser = User::factory()->create([
            'email' => 'canonical@example.com',
            'is_employee' => true,
        ]);
        $legacyEmailUser = User::factory()->create([
            'email' => 'legacy-email@example.com',
            'is_employee' => true,
        ]);
        $employee = Employee::create([
            'name' => 'Canonical Employee',
            'email' => $legacyEmailUser->email,
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => $canonicalUser->id,
        ]);

        $resolved = $service->resolveUser($employee);

        $this->assertNotNull($resolved);
        $this->assertSame($canonicalUser->id, $resolved->id);
    }

    public function test_blank_and_missing_email_mappings_fail_safely(): void
    {
        $service = app(EmployeeIdentityService::class);
        $blankEmailEmployee = Employee::create([
            'name' => 'Blank Email Employee',
            'email' => null,
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => null,
        ]);
        $unmappedEmployee = Employee::create([
            'name' => 'Missing Email User',
            'email' => 'missing-user@example.com',
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => null,
        ]);

        $this->assertNull($service->resolveUser($blankEmailEmployee));
        $this->assertNull($service->resolveUser($unmappedEmployee));
    }

    public function test_unmapped_legacy_employee_records_are_preserved_without_invention(): void
    {
        $service = app(EmployeeIdentityService::class);

        $legacyEmployee = Employee::create([
            'name' => 'Old Legacy Staff',
            'email' => 'legacy.only@housefix.test',
            'phone' => '9876543210',
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => null,
        ]);

        $this->assertNull($legacyEmployee->user_id);
        $this->assertNull($legacyEmployee->canonicalUser());
        $this->assertNull($service->resolveUser($legacyEmployee));

        // Legacy record remains in database intact
        $this->assertDatabaseHas('employees', [
            'id' => $legacyEmployee->id,
            'email' => 'legacy.only@housefix.test',
            'user_id' => null,
        ]);
    }

    public function test_employee_controller_store_sets_is_employee_and_syncs_employee_profile(): void
    {
        $perm = Permission::first();

        $response = $this->actingAs($this->admin)->post(route('employees.store'), [
            'name' => 'New Staff Member',
            'email' => 'newstaff@example.com',
            'role' => 'Site Engineer',
            'phone' => '9876543211',
            'designation' => 'Junior Engineer',
            'address' => 'Site Office 1',
            'hire_date' => '2026-05-01',
            'status' => 'active',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'direct_permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('employees.index'));

        $user = User::where('email', 'newstaff@example.com')->firstOrFail();
        $this->assertTrue((bool) $user->is_employee);

        // Verify that Employee table was safely synchronized with user_id
        $this->assertDatabaseHas('employees', [
            'email' => 'newstaff@example.com',
            'user_id' => $user->id,
            'role' => 'Site Engineer',
        ]);
    }

    public function test_employee_controller_update_maintains_sync_and_identity(): void
    {
        $user = User::factory()->create([
            'name' => 'Editable Staff',
            'email' => 'editable@example.com',
            'role' => 'Site Engineer',
            'is_employee' => true,
            'address' => 'Initial Address',
            'hire_date' => '2026-01-01',
            'status' => 'active',
        ]);
        $user->roles()->sync([$this->engineerRole->id]);

        $service = app(EmployeeIdentityService::class);
        $employee = $service->syncEmployeeRecordForUser($user);

        $perm = Permission::first();

        $response = $this->actingAs($this->admin)->put(route('employees.update', $user), [
            'name' => 'Updated Staff Name',
            'email' => 'editable@example.com',
            'role' => 'Site Engineer',
            'phone' => '9123456789',
            'designation' => 'Senior Site Engineer',
            'address' => 'Updated Address',
            'hire_date' => '2026-01-01',
            'status' => 'active',
            'direct_permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('employees.index'));

        $user->refresh();
        $this->assertSame('Updated Staff Name', $user->name);
        $this->assertTrue((bool) $user->is_employee);

        $employee->refresh();
        $this->assertSame('Updated Staff Name', $employee->name);
        $this->assertSame($user->id, $employee->user_id);
    }

    public function test_mapping_report_accurately_reflects_verified_and_unmapped_records(): void
    {
        $service = app(EmployeeIdentityService::class);

        $verifiedUser = User::factory()->create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'is_employee' => true,
        ]);

        $verifiedEmployee = Employee::create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => $verifiedUser->id,
        ]);

        $unmappedEmployee = Employee::create([
            'name' => 'Unmapped User',
            'email' => 'unmapped@example.com',
            'role' => 'Site Engineer',
            'hourly_rate' => 0.00,
            'status' => 'active',
            'user_id' => null,
        ]);

        $report = collect($service->getMappingReport());

        $verifiedEntry = $report->firstWhere('employee_id', $verifiedEmployee->id);
        $unmappedEntry = $report->firstWhere('employee_id', $unmappedEmployee->id);

        $this->assertNotNull($verifiedEntry);
        $this->assertSame('VERIFIED', $verifiedEntry['status']);
        $this->assertSame($verifiedUser->id, $verifiedEntry['user_id']);

        $this->assertNotNull($unmappedEntry);
        $this->assertSame('UNMAPPED - PRESERVED', $unmappedEntry['status']);
        $this->assertNull($unmappedEntry['user_id']);
    }
}
