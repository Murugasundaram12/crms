<?php

namespace Tests\Feature\Qa;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\MobileApiToken;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\QaTestCase;

class ProjectsDeepQaTest extends QaTestCase
{
    public function test_project_routes_require_authentication_and_resolve_create_before_show(): void
    {
        $project = Project::factory()->create([
            'project_code' => 'QA-ROUTE-001',
            'status' => 'active',
            'priority' => 'high',
        ]);

        $this->get(route('projects.index'))->assertRedirect('/login');
        $this->get(route('projects.create'))->assertRedirect('/login');
        $this->post(route('projects.store'), [])->assertRedirect('/login');

        $admin = $this->createSuperAdmin();
        $this->actingAs($admin)->get('/projects/create')
            ->assertRedirect(route('projects.index', ['create' => 1]));
        $this->actingAs($admin)->get('/projects/does-not-exist')->assertNotFound();
        $this->actingAs($admin)->post(route('projects.index'))->assertStatus(405);
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertViewIs('pages.projects.show');
    }

    public function test_authorized_user_can_list_search_create_update_and_delete_project(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create(['name' => 'Project QA Client']);
        Project::factory()->create([
            'project_code' => 'QA-LIST-001',
            'name' => 'Alpha Project',
            'client_id' => $client->id,
            'status' => 'active',
            'priority' => 'high',
        ]);
        Project::factory()->create([
            'project_code' => 'QA-LIST-002',
            'name' => 'Beta Project',
            'status' => 'cancelled',
            'priority' => 'low',
        ]);

        $this->actingAs($admin)->get(route('projects.index', ['q' => 'Alpha', 'status' => 'active']))
            ->assertOk()->assertSee('Alpha Project')->assertDontSee('Beta Project');

        $payload = $this->validProjectPayload('QA-CRUD-001', $client->id, 'Created Project');
        $this->actingAs($admin)->post(route('projects.store'), $payload)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'Project created successfully.');
        $project = Project::query()->where('project_code', 'QA-CRUD-001')->firstOrFail();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'client_id' => $client->id, 'progress' => 0]);
        $this->actingAs($admin)->get(route('projects.edit', $project))
            ->assertRedirect(route('projects.index', ['edit' => $project->id]));

        $this->actingAs($admin)->put(route('projects.update', $project), $this->validProjectPayload(
            'QA-CRUD-001',
            $client->id,
            'Updated Project'
        ))->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Project']);

        $this->actingAs($admin)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', 'Project deleted successfully.');
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_validation_enforces_actual_rules_and_foreign_keys(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create();
        Project::factory()->create(['project_code' => 'QA-UNIQUE-001']);

        $this->actingAs($admin)->from(route('projects.index'))->post(route('projects.store'), [
            'project_code' => 'QA-UNIQUE-001',
            'client_id' => 999999,
            'name' => str_repeat('N', 256),
            'type' => 'Civil',
            'priority' => 'invalid',
            'status' => 'invalid',
            'progress' => 101,
            'start_date' => 'not-a-date',
            'end_date' => '2020-01-01',
            'location' => 'not-a-url',
        ])->assertRedirect(route('projects.index'))
            ->assertSessionHasErrors(['project_code', 'client_id', 'name', 'priority', 'status', 'progress', 'start_date', 'location']);
    }

    public function test_project_create_validation_errors_render_above_each_invalid_field(): void
    {
        $admin = $this->createSuperAdmin();
        Client::factory()->create(['name' => 'Validation Client']);

        $this->actingAs($admin)->from(route('projects.index'))->post(route('projects.store'), [
            'project_code' => str_repeat('X', 51),
            'client_id' => 999999,
            'name' => '',
            'type' => '',
            'priority' => 'invalid',
            'status' => 'invalid',
            'start_date' => 'not-a-date',
            'end_date' => '2020-01-01',
            'location' => 'not-a-url',
        ])->assertRedirect(route('projects.index'))
            ->assertSessionHasErrors(['project_code', 'client_id', 'name', 'type', 'priority', 'status', 'start_date', 'location']);

        $html = $this->actingAs($admin)->get(route('projects.index'))->getContent();

        foreach (['project_code', 'client_id', 'name', 'type', 'priority', 'status', 'start_date', 'location'] as $field) {
            $this->assertMatchesRegularExpression(
                '/validation-error[\\s\\S]*name=["\\\']' . preg_quote($field, '/') . '["\\\']/',
                $html,
                "Validation error for {$field} must appear immediately above its field."
            );
        }
    }

    public function test_project_delete_is_blocked_when_related_task_exists(): void
    {
        $admin = $this->createSuperAdmin();
        $project = Project::factory()->create(['project_code' => 'QA-DEPENDENCY-001']);
        $task = Task::factory()->create(['project_id' => $project->id, 'type' => 'general', 'priority' => 'high', 'status' => 'pending']);

        $this->actingAs($admin)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))->assertSessionHas('error');
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'project_id' => $project->id]);
    }

    public function test_users_without_project_permissions_cannot_use_direct_web_actions(): void
    {
        $project = Project::factory()->create(['project_code' => 'QA-RBAC-001']);
        $user = $this->createManagerWithPermissions([]);

        foreach ([
            ['method' => 'get', 'route' => route('projects.index')],
            ['method' => 'post', 'route' => route('projects.store')],
            ['method' => 'put', 'route' => route('projects.update', $project)],
            ['method' => 'delete', 'route' => route('projects.destroy', $project)],
        ] as $case) {
            $response = $this->actingAs($user)->{$case['method']}($case['route'], $case['method'] === 'get' ? [] : $this->validProjectPayload(
                'Denied Project',
                $project->client_id,
                'Denied Project'
            ));
            $response->assertRedirect();
        }

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_project_task_and_client_relationships_are_preserved(): void
    {
        $admin = $this->createSuperAdmin();
        $clientA = Client::factory()->create(['name' => 'Project Client A']);
        $clientB = Client::factory()->create(['name' => 'Project Client B']);
        $projectA = Project::factory()->create(['client_id' => $clientA->id, 'project_code' => 'QA-REL-001']);
        $projectB = Project::factory()->create(['client_id' => $clientB->id, 'project_code' => 'QA-REL-002']);
        $taskA = Task::factory()->create(['project_id' => $projectA->id, 'title' => 'Task A', 'type' => 'general', 'priority' => 'high', 'status' => 'pending']);
        $taskB = Task::factory()->create(['project_id' => $projectB->id, 'title' => 'Task B', 'type' => 'general', 'priority' => 'high', 'status' => 'pending']);

        $this->actingAs($admin)->get(route('projects.show', $projectA))
            ->assertOk()->assertSee('Project Client A')->assertSee('Task A')->assertDontSee('Task B');
        $this->assertSame($clientA->id, $projectA->fresh()->client_id);
        $this->assertSame($projectA->id, $taskA->fresh()->project_id);
        $this->assertNotSame($projectA->id, $taskB->fresh()->project_id);
    }

    public function test_mobile_api_project_reads_enforce_object_scope(): void
    {
        [$user, $ownedProject, $otherProject] = $this->scopedApiProjectFixture(['projects-list']);

        $headers = $this->apiHeaders($user);
        $this->withHeaders($headers)->getJson('/api/projects/' . $ownedProject->id)->assertOk();
        $this->withHeaders($headers)->getJson('/api/projects/' . $otherProject->id)
            ->assertForbidden()->assertJson(['message' => 'Forbidden.']);
        $this->withHeaders($headers)->getJson('/api/projects')->assertOk()
            ->assertJsonFragment(['name' => $ownedProject->name])
            ->assertJsonMissing(['name' => $otherProject->name]);
    }

    public function test_mobile_api_project_update_cannot_modify_project_outside_user_scope(): void
    {
        [$user, $ownedProject, $otherProject] = $this->scopedApiProjectFixture(['projects-edit']);

        $this->withHeaders($this->apiHeaders($user))->putJson('/api/projects/' . $otherProject->id, [
            ...$this->validProjectPayload('QA-API-OTHER-001', $otherProject->client_id, 'Unauthorized Project Update'),
        ])->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $otherProject->id, 'name' => $otherProject->name]);
        $this->assertDatabaseHas('projects', ['id' => $ownedProject->id]);
    }

    public function test_mobile_api_project_delete_cannot_delete_project_outside_user_scope(): void
    {
        [$user, $ownedProject, $otherProject] = $this->scopedApiProjectFixture(['projects-delete']);

        $this->withHeaders($this->apiHeaders($user))->deleteJson('/api/projects/' . $otherProject->id)
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $otherProject->id]);
        $this->assertDatabaseHas('projects', ['id' => $ownedProject->id]);
    }

    public function test_mobile_api_project_update_inside_scope_succeeds_and_delete_honors_dependencies(): void
    {
        [$user, $ownedProject] = $this->scopedApiProjectFixture(['projects-edit', 'projects-delete']);
        Task::factory()->create(['project_id' => $ownedProject->id, 'type' => 'general', 'priority' => 'high', 'status' => 'pending']);
        $headers = $this->apiHeaders($user);

        $this->withHeaders($headers)->putJson('/api/projects/' . $ownedProject->id, [
            ...$this->validProjectPayload('QA-API-OWNED-001', $ownedProject->client_id, 'Authorized Project Update'),
        ])->assertOk();
        $this->assertDatabaseHas('projects', ['id' => $ownedProject->id, 'name' => 'Authorized Project Update']);

        $this->withHeaders($headers)->deleteJson('/api/projects/' . $ownedProject->id)->assertStatus(409);
        $this->assertDatabaseHas('projects', ['id' => $ownedProject->id]);
    }

    /** @return array<string, mixed> */
    private function validProjectPayload(string $code, int $clientId, string $name): array
    {
        return [
            'project_code' => $code,
            'client_id' => $clientId,
            'name' => $name,
            'description' => 'Project QA description',
            'type' => 'Residential',
            'priority' => 'high',
            'status' => 'active',
            'progress' => 25,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'location' => 'https://example.test/project-location',
        ];
    }

    /** @return array{0: User, 1: Project, 2: Project} */
    private function scopedApiProjectFixture(array $permissions): array
    {
        $ownedClient = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $user = $this->apiProjectUser($permissions);
        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
        $ownedProject = Project::factory()->create(['client_id' => $ownedClient->id, 'manager_id' => $employee->id]);
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);

        return [$user, $ownedProject, $otherProject];
    }

    private function apiProjectUser(array $permissions): User
    {
        $role = Role::query()->create(['name' => 'QA Project ' . Str::random(8)]);
        $permissionIds = [];
        foreach ($permissions as $key) {
            $permissionIds[] = Permission::query()->firstOrCreate(
                ['key' => $key],
                ['name' => ucwords(str_replace('-', ' ', $key))]
            )->id;
        }
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'role' => $role->name,
            'email' => 'qa-project-api-' . Str::random(10) . '@example.test',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    /** @return array<string, string> */
    private function apiHeaders(User $user): array
    {
        $plainToken = 'qa-project-' . Str::random(48);
        $deviceId = 'qa-project-device-' . Str::random(10);
        EmployeeDevice::query()->create([
            'employee_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => 'QA Project Device',
        ]);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'QA Project Token',
            'device_id' => $deviceId,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(10),
        ]);

        return ['Authorization' => 'Bearer ' . $plainToken, 'Accept' => 'application/json'];
    }
}
