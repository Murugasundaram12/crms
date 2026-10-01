<?php

namespace Tests\Feature\Qa;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\MobileApiToken;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\QaTestCase;

class ClientsDeepQaTest extends QaTestCase
{
    public function test_client_routes_require_authentication_and_expected_permissions(): void
    {
        $client = Client::factory()->create(['phone' => '9876543210']);

        $this->get(route('clients.index'))->assertRedirect('/login');
        $this->get(route('clients.show', $client))->assertRedirect('/login');
        $this->get(route('clients.create'))->assertRedirect('/login');
        $this->post(route('clients.store'), [])->assertRedirect('/login');

        $manager = $this->createManagerWithPermissions(['clients-list']);
        $this->actingAs($manager)->get(route('clients.index'))->assertOk();
        $this->actingAs($manager)->get(route('clients.show', $client))
            ->assertRedirect(route('clients.index', ['highlight' => $client->id]));
        $this->actingAs($manager)->get(route('clients.create'))->assertRedirect();
        $this->actingAs($manager)->post(route('clients.store'), [])->assertRedirect();
    }

    public function test_client_route_ordering_and_missing_models_are_safe(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create(['phone' => '9876543210']);

        $this->actingAs($admin)->get('/clients/create')
            ->assertRedirect(route('clients.index'));
        $this->actingAs($admin)->get('/clients/does-not-exist')->assertNotFound();
        $this->actingAs($admin)->get('/clients/999999')->assertNotFound();
        $this->actingAs($admin)->get(route('clients.show', $client))->assertRedirect();
        $this->actingAs($admin)->post(route('clients.index'))->assertStatus(405);
    }

    public function test_authorized_user_can_list_search_filter_and_paginate_clients(): void
    {
        $admin = $this->createSuperAdmin();
        Client::factory()->create([
            'name' => 'Alpha Deep Client',
            'company_name' => 'Alpha Works',
            'phone' => '9876543210',
            'status' => 'active',
        ]);
        Client::factory()->create([
            'name' => 'Beta Deep Client',
            'company_name' => 'Beta Works',
            'phone' => '9876543211',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)->get(route('clients.index', [
            'q' => 'Alpha',
            'status' => 'active',
        ]))->assertOk()->assertSee('Alpha Deep Client')->assertDontSee('Beta Deep Client');

        $this->actingAs($admin)->get(route('clients.index', [
            'q' => 'no-client-matches-this-term',
        ]))->assertOk()->assertSee('No clients added yet');
    }

    public function test_authorized_user_can_create_update_and_delete_unreferenced_client(): void
    {
        $admin = $this->createSuperAdmin();

        $payload = $this->validClientPayload('Create Deep Client', 'create-deep@example.test');
        $this->actingAs($admin)->post(route('clients.store'), $payload)
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Client created successfully.');

        $client = Client::query()->where('email', $payload['email'])->firstOrFail();
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Create Deep Client',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('clients.edit', $client))
            ->assertRedirect(route('clients.index', ['edit' => $client->id]));

        $update = $this->validClientPayload('Updated Deep Client', 'updated-deep@example.test');
        $this->actingAs($admin)->put(route('clients.update', $client), $update)
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Client updated successfully.');
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Updated Deep Client']);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success', 'Client deleted successfully.');
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_validation_covers_required_format_unique_and_boundary_rules(): void
    {
        $admin = $this->createSuperAdmin();
        $existing = Client::factory()->create([
            'email' => 'existing-client@example.test',
            'phone' => '9876543210',
        ]);

        $this->actingAs($admin)->from(route('clients.index'))->post(route('clients.store'), [
            'name' => str_repeat('N', 256),
            'email' => $existing->email,
            'phone' => '12345',
            'status' => 'invalid-status',
        ])->assertRedirect(route('clients.index'))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'status']);

        $this->actingAs($admin)->from(route('clients.index'))->post(route('clients.store'), [
            'name' => '   ',
            'email' => 'not-an-email',
            'phone' => '9876543210',
            'status' => 'active',
        ])->assertRedirect(route('clients.index'))
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseHas('clients', ['id' => $existing->id]);
    }

    public function test_client_delete_is_blocked_when_related_records_exist(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create(['phone' => '9876543210']);
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->actingAs($admin)->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'client_id' => $client->id]);
    }

    public function test_users_without_each_client_permission_cannot_use_direct_web_actions(): void
    {
        $client = Client::factory()->create(['phone' => '9876543210']);
        $cases = [
            ['permission' => 'clients-list', 'method' => 'get', 'route' => route('clients.index')],
            ['permission' => 'clients-create', 'method' => 'post', 'route' => route('clients.store')],
            ['permission' => 'clients-edit', 'method' => 'put', 'route' => route('clients.update', $client)],
            ['permission' => 'clients-delete', 'method' => 'delete', 'route' => route('clients.destroy', $client)],
        ];

        foreach ($cases as $case) {
            $user = $this->createManagerWithPermissions([]);
            $response = $this->actingAs($user)->{$case['method']}($case['route'], $case['method'] === 'get' ? [] : $this->validClientPayload('Denied Client', 'denied-' . Str::random(8) . '@example.test'));
            $response->assertRedirect();
            $this->assertDatabaseMissing('clients', ['name' => 'Denied Client']);
        }
    }

    public function test_client_project_relationship_is_preserved_and_delete_is_blocked(): void
    {
        $admin = $this->createSuperAdmin();
        $clientA = Client::factory()->create(['name' => 'Client A', 'phone' => '9876543210']);
        $clientB = Client::factory()->create(['name' => 'Client B', 'phone' => '9876543211']);

        $projectA = Project::factory()->create(['client_id' => $clientA->id, 'name' => 'Project A']);
        $projectB = Project::factory()->create(['client_id' => $clientB->id, 'name' => 'Project B']);

        $this->assertTrue($clientA->projects()->whereKey($projectA->id)->exists());
        $this->assertFalse($clientA->projects()->whereKey($projectB->id)->exists());
        $this->actingAs($admin)->get(route('clients.show', $clientA))->assertRedirect();

        $this->actingAs($admin)->delete(route('clients.destroy', $clientA))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('clients', ['id' => $clientA->id]);
    }

    public function test_mobile_api_requires_permissions_and_enforces_client_object_scope_on_reads(): void
    {
        $clientA = Client::factory()->create(['phone' => '9876543210']);
        $clientB = Client::factory()->create(['phone' => '9876543211']);
        $user = $this->apiClientUser(['clients-list', 'clients-edit', 'clients-delete']);
        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
        Project::factory()->create(['client_id' => $clientA->id, 'manager_id' => $employee->id]);

        $headers = $this->apiHeaders($user);
        $this->withHeaders($headers)->getJson('/api/clients')->assertOk()
            ->assertJsonFragment(['name' => $clientA->name])
            ->assertJsonMissing(['name' => $clientB->name]);
        $this->withHeaders($headers)->getJson('/api/clients/' . $clientB->id)
            ->assertForbidden();
    }

    public function test_mobile_api_client_update_cannot_modify_client_outside_user_scope(): void
    {
        $clientA = Client::factory()->create(['phone' => '9876543210']);
        $clientB = Client::factory()->create(['name' => 'Protected Client B', 'phone' => '9876543211']);
        $user = $this->apiClientUser(['clients-list', 'clients-edit']);
        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
        Project::factory()->create(['client_id' => $clientA->id, 'manager_id' => $employee->id]);

        $response = $this->withHeaders($this->apiHeaders($user))->putJson('/api/clients/' . $clientB->id, [
            ...$this->validClientPayload('Unauthorized API Update', 'api-update@example.test'),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('clients', ['id' => $clientB->id, 'name' => 'Protected Client B']);
    }

    public function test_mobile_api_client_delete_cannot_delete_client_outside_user_scope(): void
    {
        $clientA = Client::factory()->create(['phone' => '9876543210']);
        $clientB = Client::factory()->create(['name' => 'Protected Client B', 'phone' => '9876543211']);
        $user = $this->apiClientUser(['clients-list', 'clients-delete']);
        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
        Project::factory()->create(['client_id' => $clientA->id, 'manager_id' => $employee->id]);

        $this->withHeaders($this->apiHeaders($user))->deleteJson('/api/clients/' . $clientB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => $clientB->id, 'name' => 'Protected Client B']);
    }

    public function test_mobile_api_client_update_and_delete_succeed_inside_user_scope(): void
    {
        $client = Client::factory()->create(['phone' => '9876543210']);
        $user = $this->apiClientUser(['clients-edit', 'clients-delete']);
        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);
        Project::factory()->create(['client_id' => $client->id, 'manager_id' => $employee->id]);

        $headers = $this->apiHeaders($user);
        $this->withHeaders($headers)->putJson('/api/clients/' . $client->id, [
            ...$this->validClientPayload('Authorized API Update', 'authorized-api-update@example.test'),
        ])->assertOk();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Authorized API Update']);

        $this->withHeaders($headers)->deleteJson('/api/clients/' . $client->id)
            ->assertStatus(409);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Authorized API Update']);
    }

    /** @return array<string, mixed> */
    private function validClientPayload(string $name, string $email): array
    {
        return [
            'name' => $name,
            'company_name' => 'Deep QA Company',
            'email' => $email,
            'phone' => '9876543210',
            'address' => 'QA Address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'country' => 'India',
            'status' => 'active',
            'notes' => 'QA client record',
        ];
    }

    private function apiClientUser(array $permissions): User
    {
        $role = Role::query()->create(['name' => 'QA Client ' . Str::random(8)]);
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
            'email' => 'qa-client-api-' . Str::random(10) . '@example.test',
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    /** @return array<string, string> */
    private function apiHeaders(User $user): array
    {
        $plainToken = 'qa-client-' . Str::random(48);
        $deviceId = 'qa-client-device-' . Str::random(10);

        EmployeeDevice::query()->create([
            'employee_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => 'QA Client Device',
        ]);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'QA Client Token',
            'device_id' => $deviceId,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(10),
        ]);

        return [
            'Authorization' => 'Bearer ' . $plainToken,
            'Accept' => 'application/json',
        ];
    }
}
