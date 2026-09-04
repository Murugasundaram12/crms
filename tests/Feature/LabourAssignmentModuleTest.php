<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourRole;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourAssignmentModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected Project $project;
    protected Labour $labour;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->superAdmin = User::factory()->create([
            'email' => 'admin_assign_' . uniqid() . '@example.com',
            'role' => 'Super Admin',
        ]);

        $role = LabourRole::firstOrCreate(
            ['name' => 'General Worker Test'],
            ['salary_type' => 'daily', 'salary' => 500.00]
        );

        $this->labour = Labour::create([
            'name' => 'Assignment Labour ' . uniqid(),
            'phone' => '9876543201',
            'phone_number' => '9876543201',
            'labour_role_id' => $role->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $client = Client::create([
            'name' => 'Assignment Client ' . uniqid(),
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'name' => 'Assignment Site ' . uniqid(),
            'project_code' => 'PRJ-ASG-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);
    }

    protected function createLabour(string $name): Labour
    {
        $role = LabourRole::firstOrCreate(
            ['name' => 'General Worker Test'],
            ['salary_type' => 'daily', 'salary' => 500.00]
        );

        return Labour::create([
            'name' => $name . ' ' . uniqid(),
            'phone' => '98765432' . rand(10, 99),
            'phone_number' => '98765432' . rand(10, 99),
            'labour_role_id' => $role->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);
    }

    protected function createUserWithPermissions(array $permissionKeys): User
    {
        $roleName = 'Custom Role ' . uniqid();
        $role = Role::create([
            'name' => $roleName,
        ]);

        foreach ($permissionKeys as $key) {
            $permission = Permission::firstOrCreate(
                ['key' => $key],
                ['name' => ucwords(str_replace('-', ' ', $key))]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::factory()->create([
            'email' => 'user_' . uniqid() . '@example.com',
            'role' => $roleName,
        ]);
    }

    // ==========================================
    // 1. DATE FILTER TESTS (1 - 7)
    // ==========================================

    public function test_no_date_filter_returns_normal_assignment_listing(): void
    {
        $labour1 = $this->createLabour('Worker Alpha');
        $labour2 = $this->createLabour('Worker Beta');

        LabourAssignment::create([
            'labour_id' => $labour1->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'status' => 'active',
        ]);

        LabourAssignment::create([
            'labour_id' => $labour2->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-15',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index'));

        $response->assertOk();
        $response->assertSee($labour1->name);
        $response->assertSee($labour2->name);
    }

    public function test_selected_date_inside_assignment_range_shows_assignment(): void
    {
        $labourIn = $this->createLabour('Worker Inside');

        LabourAssignment::create([
            'labour_id' => $labourIn->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', [
            'date' => '2026-09-05',
        ]));

        $response->assertOk();
        $response->assertSee($labourIn->name);
    }

    public function test_date_before_start_date_hides_assignment(): void
    {
        $labourBefore = $this->createLabour('Worker Before');

        $asg = LabourAssignment::create([
            'labour_id' => $labourBefore->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', [
            'date' => '2026-09-04',
        ]));

        $response->assertOk();
        $this->assertFalse($response->viewData('assignments')->contains('id', $asg->id));
        $response->assertDontSee(route('labour_assignments.edit', $asg));
    }

    public function test_date_after_end_date_hides_assignment(): void
    {
        $labourAfter = $this->createLabour('Worker After');

        $asg = LabourAssignment::create([
            'labour_id' => $labourAfter->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', [
            'date' => '2026-09-06',
        ]));

        $response->assertOk();
        $this->assertFalse($response->viewData('assignments')->contains('id', $asg->id));
        $response->assertDontSee(route('labour_assignments.edit', $asg));
    }

    public function test_start_date_is_inclusive_in_date_filter(): void
    {
        $labourStart = $this->createLabour('Worker Start');

        $asg = LabourAssignment::create([
            'labour_id' => $labourStart->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', [
            'date' => '2026-09-05',
        ]));

        $response->assertOk();
        $this->assertTrue($response->viewData('assignments')->contains('id', $asg->id));
        $response->assertSee(route('labour_assignments.edit', $asg));
    }

    public function test_end_date_is_inclusive_in_date_filter(): void
    {
        $labourEnd = $this->createLabour('Worker End');

        $asg = LabourAssignment::create([
            'labour_id' => $labourEnd->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', [
            'date' => '2026-09-10',
        ]));

        $response->assertOk();
        $this->assertTrue($response->viewData('assignments')->contains('id', $asg->id));
        $response->assertSee(route('labour_assignments.edit', $asg));
    }

    public function test_multiple_assignments_are_filtered_correctly_by_date(): void
    {
        $labour1 = $this->createLabour('Batch One Labour');
        $labour2 = $this->createLabour('Batch Two Labour');

        $asg1 = LabourAssignment::create([
            'labour_id' => $labour1->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'status' => 'active',
        ]);

        $asg2 = LabourAssignment::create([
            'labour_id' => $labour2->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-12',
            'status' => 'active',
        ]);

        // Filter on Sept 3: asg1 shows, asg2 hidden
        $res1 = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', ['date' => '2026-09-03']));
        $res1->assertOk();
        $this->assertTrue($res1->viewData('assignments')->contains('id', $asg1->id));
        $this->assertFalse($res1->viewData('assignments')->contains('id', $asg2->id));
        $res1->assertSee(route('labour_assignments.edit', $asg1));
        $res1->assertDontSee(route('labour_assignments.edit', $asg2));

        // Filter on Sept 10: asg2 shows, asg1 hidden
        $res2 = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index', ['date' => '2026-09-10']));
        $res2->assertOk();
        $this->assertTrue($res2->viewData('assignments')->contains('id', $asg2->id));
        $this->assertFalse($res2->viewData('assignments')->contains('id', $asg1->id));
        $res2->assertSee(route('labour_assignments.edit', $asg2));
        $res2->assertDontSee(route('labour_assignments.edit', $asg1));
    }

    // ==========================================
    // 2. EDIT TESTS (8 - 13)
    // ==========================================

    public function test_edit_page_is_accessible_with_edit_permission(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $user = $this->createUserWithPermissions(['labour-assignments-edit']);

        $response = $this->actingAs($user)->get(route('labour_assignments.edit', $asg));

        $response->assertOk();
        $response->assertSee($this->labour->name);
        $response->assertSee($this->project->name);
        $response->assertSee('Edit Labour Site Assignment');
    }

    public function test_assignment_can_be_updated(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('labour_assignments.update', $asg), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-12',
            'status' => 'completed',
            'notes' => 'Updated notes successfully',
        ]);

        $response->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseHas('labour_assignments', [
            'id' => $asg->id,
            'status' => 'completed',
            'notes' => 'Updated notes successfully',
        ]);
    }

    public function test_updated_start_and_end_dates_are_saved_correctly(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('labour_assignments.update', $asg), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-25',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseHas('labour_assignments', [
            'id' => $asg->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-25',
        ]);
    }

    public function test_overlapping_active_assignment_is_rejected_on_update(): void
    {
        // Assignment 1: Sept 1 to Sept 10
        $asg1 = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Assignment 2: Sept 20 to Sept 30
        $asg2 = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        // Try updating Assignment 2 to overlap with Assignment 1 (Sept 5 to Sept 25)
        $response = $this->actingAs($this->superAdmin)->put(route('labour_assignments.update', $asg2), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-25',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('labour_id');
        $this->assertEquals('2026-09-20', $asg2->fresh()->start_date->toDateString());
    }

    public function test_current_assignment_is_not_detected_as_its_own_overlap_on_update(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Shift dates slightly (Sept 2 to Sept 9) - should not collide with itself
        $response = $this->actingAs($this->superAdmin)->put(route('labour_assignments.update', $asg), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-09',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_assignments', [
            'id' => $asg->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-09',
        ]);
    }

    public function test_unauthorized_user_cannot_edit_assignment(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // User without labour-assignments-edit permission
        $unauthorizedUser = $this->createUserWithPermissions(['labour-assignments-list']);

        // GET edit
        $resGet = $this->actingAs($unauthorizedUser)->get(route('labour_assignments.edit', $asg));
        $resGet->assertRedirect();
        $resGet->assertSessionHas('error', 'You do not have permission to access this module.');

        // PUT update
        $resPut = $this->actingAs($unauthorizedUser)->put(route('labour_assignments.update', $asg), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-15',
            'status' => 'active',
        ]);
        $resPut->assertRedirect();
        $resPut->assertSessionHas('error', 'You do not have permission to access this module.');
    }

    // ==========================================
    // 3. PERMISSIONS TESTS (14 - 18)
    // ==========================================

    public function test_list_permission_controls_assignment_listing(): void
    {
        $unauthorizedUser = $this->createUserWithPermissions([]);
        $resBlocked = $this->actingAs($unauthorizedUser)->get(route('labour_assignments.index'));
        $resBlocked->assertRedirect();
        $resBlocked->assertSessionHas('error', 'You do not have permission to access this module.');

        $authorizedUser = $this->createUserWithPermissions(['labour-assignments-list']);
        $resAllowed = $this->actingAs($authorizedUser)->get(route('labour_assignments.index'));
        $resAllowed->assertOk();
    }

    public function test_create_permission_controls_creation(): void
    {
        $unauthorizedUser = $this->createUserWithPermissions(['labour-assignments-list']);
        $resBlocked = $this->actingAs($unauthorizedUser)->post(route('labour_assignments.store'), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);
        $resBlocked->assertRedirect();
        $resBlocked->assertSessionHas('error', 'You do not have permission to access this module.');

        $authorizedUser = $this->createUserWithPermissions(['labour-assignments-list', 'labour-assignments-create']);
        $resAllowed = $this->actingAs($authorizedUser)->post(route('labour_assignments.store'), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);
        $resAllowed->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseHas('labour_assignments', [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_edit_permission_controls_edit_and_update(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $unauthorizedUser = $this->createUserWithPermissions(['labour-assignments-list']);
        $resBlocked = $this->actingAs($unauthorizedUser)->get(route('labour_assignments.edit', $asg));
        $resBlocked->assertRedirect();
        $resBlocked->assertSessionHas('error', 'You do not have permission to access this module.');

        $authorizedUser = $this->createUserWithPermissions(['labour-assignments-list', 'labour-assignments-edit']);
        $resAllowed = $this->actingAs($authorizedUser)->get(route('labour_assignments.edit', $asg));
        $resAllowed->assertOk();
    }

    public function test_delete_permission_controls_deletion(): void
    {
        $asg = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->superAdmin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $unauthorizedUser = $this->createUserWithPermissions(['labour-assignments-list']);
        $resBlocked = $this->actingAs($unauthorizedUser)->delete(route('labour_assignments.destroy', $asg));
        $resBlocked->assertRedirect();
        $resBlocked->assertSessionHas('error', 'You do not have permission to access this module.');
        $this->assertDatabaseHas('labour_assignments', ['id' => $asg->id]);

        $authorizedUser = $this->createUserWithPermissions(['labour-assignments-list', 'labour-assignments-delete']);
        $resAllowed = $this->actingAs($authorizedUser)->delete(route('labour_assignments.destroy', $asg));
        $resAllowed->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseMissing('labour_assignments', ['id' => $asg->id]);
    }

    public function test_super_admin_continues_to_bypass_permission_checks(): void
    {
        // Super admin has role = 'Super Admin' with no individual permissions assigned in DB
        $resIndex = $this->actingAs($this->superAdmin)->get(route('labour_assignments.index'));
        $resIndex->assertOk();

        $resStore = $this->actingAs($this->superAdmin)->post(route('labour_assignments.store'), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);
        $resStore->assertRedirect(route('labour_assignments.index'));

        $asg = LabourAssignment::where('labour_id', $this->labour->id)->firstOrFail();

        $resEdit = $this->actingAs($this->superAdmin)->get(route('labour_assignments.edit', $asg));
        $resEdit->assertOk();

        $resUpdate = $this->actingAs($this->superAdmin)->put(route('labour_assignments.update', $asg), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-12',
            'status' => 'active',
        ]);
        $resUpdate->assertRedirect(route('labour_assignments.index'));

        $resDelete = $this->actingAs($this->superAdmin)->delete(route('labour_assignments.destroy', $asg));
        $resDelete->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseMissing('labour_assignments', ['id' => $asg->id]);
    }
}
