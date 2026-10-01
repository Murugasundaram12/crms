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

class RouteRbacApiSecurityQaTest extends QaTestCase
{
    public function test_static_create_routes_resolve_before_dynamic_resource_routes(): void
    {
        $admin = $this->createSuperAdmin();
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id, 'type' => 'general']);

        $this->actingAs($admin)->get(route('projects.create'))
            ->assertRedirect(route('projects.index', ['create' => 1]));
        $this->actingAs($admin)->get(route('tasks.create'))
            ->assertRedirect(route('tasks.index'));
        $this->actingAs($admin)->get(route('projects.show', $project))
            ->assertOk()
            ->assertViewIs('pages.projects.show');
        $this->actingAs($admin)->get(route('tasks.show', $task))
            ->assertRedirect(route('tasks.index', ['highlight' => $task->id]));
    }

    public function test_logout_is_post_only_and_invalidates_the_authenticated_session(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)->get('/logout')->assertStatus(405);
        $this->actingAs($admin)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_web_dashboard_or_sensitive_mutation_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/projects/store', [])->assertRedirect('/login');
        $this->get('/server-commands/optimize')->assertRedirect('/login');
    }

    public function test_permission_middleware_allows_permitted_module_and_denies_direct_url_access(): void
    {
        $manager = $this->createManagerWithPermissions(['projects-list']);

        $this->actingAs($manager)->get('/projects')->assertOk();
        $denied = $this->actingAs($manager)->get('/clients');
        $denied->assertRedirect()->assertSessionHas('error', 'You do not have permission to access this module.');
        $this->actingAs($manager)->post('/clients/store', [])->assertRedirect();
        $this->assertSame(302, $this->actingAs($manager)->get('/server-commands/optimize')->getStatusCode());
    }

    public function test_api_requires_bearer_token_and_rejects_invalid_token_without_server_error(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->withHeaders(['Authorization' => 'Bearer invalid-qa-token'])
            ->getJson('/api/dashboard')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->postJson('/api/login', [])->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_authenticated_api_user_is_allowed_and_permission_checks_return_json_403(): void
    {
        $admin = $this->apiUser('Super Admin');
        $this->withHeaders($admin['headers'])->getJson('/api/dashboard')->assertOk();

        $employee = $this->apiUser('Employee');
        $this->withHeaders($employee['headers'])
            ->getJson('/api/roles')
            ->assertForbidden()
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_api_object_scope_blocks_employee_from_unowned_project(): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['key' => 'projects-list'],
            ['name' => 'List Projects']
        );
        $role = Role::query()->firstOrCreate(['name' => 'QA API Employee']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $employeeUser = User::factory()->create([
            'role' => 'QA API Employee',
            'email' => 'qa-api-' . Str::lower(Str::random(12)) . '@example.test',
            'status' => 'active',
        ]);
        $employee = Employee::query()->create([
            'name' => $employeeUser->name,
            'email' => $employeeUser->email,
            'status' => 'active',
        ]);
        $employeeUser->roles()->sync([$role->id]);
        $employeeUser->clearResolvedPermissions();

        $ownedClient = Client::factory()->create();
        $ownedProject = Project::factory()->create(['client_id' => $ownedClient->id, 'manager_id' => $employee->id]);
        $otherProject = Project::factory()->create();
        $api = $this->apiTokenFor($employeeUser);

        $this->withHeaders($api['headers'])
            ->getJson('/api/projects/' . $ownedProject->id)
            ->assertOk();
        $this->withHeaders($api['headers'])
            ->getJson('/api/projects/' . $otherProject->id)
            ->assertForbidden()
            ->assertJson(['message' => 'Forbidden.']);
    }

    public function test_public_api_settings_do_not_expose_credentials_or_tokens(): void
    {
        foreach (['/api/V1/getAppSettings', '/api/V1/getModuleSettings', '/api/V1/getMapSettings', '/api/tracking/settings'] as $endpoint) {
            $response = $this->getJson($endpoint)->assertOk();
            $json = strtolower($response->getContent());
            $this->assertStringNotContainsString('password', $json, $endpoint);
            $this->assertStringNotContainsString('secret', $json, $endpoint);
            $this->assertStringNotContainsString('bearer ', $json, $endpoint);
        }
    }

    public function test_general_device_payload_omits_messaging_token_but_update_response_preserves_it(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
            'status' => 'active',
            'email' => 'qa-security-' . Str::lower(Str::random(12)) . '@example.test',
        ]);
        $api = $this->apiTokenFor($user);
        $device = EmployeeDevice::query()->where('employee_id', $user->id)->firstOrFail();
        $storedToken = 'qa-redacted-messaging-' . Str::random(12);
        $device->update(['messaging_token' => $storedToken]);

        $trackingResponse = $this->withHeaders($api['headers'])
            ->getJson('/api/employees/track')
            ->assertOk();
        $this->assertStringNotContainsString($storedToken, $trackingResponse->getContent());
        $trackingResponse->assertJsonMissingPath('data.0.latest_location.messaging_token');

        $this->withHeaders($api['headers'])
            ->postJson('/api/messagingToken', [
                'deviceId' => $device->device_id,
                'token' => 'qa-legitimate-messaging-' . Str::random(12),
            ])
            ->assertOk()
            ->assertJsonPath('device.messaging_token', fn ($value) => is_string($value) && str_starts_with($value, 'qa-legitimate-messaging-'));
    }

    /** @return array{headers: array<string, string>} */
    private function apiUser(string $role): array
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'email' => 'qa-security-' . Str::lower(Str::random(12)) . '@example.test',
        ]);

        if ($role === 'Employee') {
            $user->clearResolvedPermissions();
        }

        return $this->apiTokenFor($user);
    }

    /** @return array{headers: array<string, string>} */
    private function apiTokenFor(User $user): array
    {
        $plainToken = 'qa-' . Str::random(48);
        $deviceId = 'qa-device-' . Str::random(12);

        EmployeeDevice::query()->create([
            'employee_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => 'QA Security Device',
        ]);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'QA Security Token',
            'device_id' => $deviceId,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(10),
        ]);

        return ['headers' => ['Authorization' => 'Bearer ' . $plainToken, 'Accept' => 'application/json']];
    }
}
