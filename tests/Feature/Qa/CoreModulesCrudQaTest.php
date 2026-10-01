<?php

namespace Tests\Feature\Qa;

use App\Models\Client;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class CoreModulesCrudQaTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('tasks');
            Schema::dropIfExists('projects');
            Schema::dropIfExists('clients');
            Schema::dropIfExists('vendors');
            Schema::dropIfExists('labours');
            Schema::dropIfExists('labour_roles');

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
                $table->timestamps();
            });

            Schema::create('tasks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id');
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->boolean('auto_repeat')->default(false);
                $table->unsignedBigInteger('recurring_source_id')->nullable();
                $table->string('priority')->default('Medium');
                $table->string('status')->default('Pending');
                $table->date('due_date')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->decimal('estimated_hours', 8, 2)->default(0);
                $table->decimal('logged_hours', 8, 2)->default(0);
                $table->boolean('is_important')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            Schema::create('vendors', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('advance_amount', 14, 2)->default(0);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->timestamps();
            });

            Schema::create('labour_roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('salary_type')->default('daily');
                $table->decimal('salary', 14, 2)->default(0);
                $table->timestamps();
            });

            Schema::create('labours', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('job_title')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('labour_role')->nullable();
                $table->unsignedBigInteger('labour_role_id')->nullable();
                $table->string('gender')->nullable();
                $table->decimal('salary', 14, 2)->default(0);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->string('government_image')->nullable();
                $table->string('government_photo')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Test Client List, Create, Validation, Edit, and Persistence.
     */
    public function test_client_crud_lifecycle(): void
    {
        $admin = $this->createSuperAdmin();

        // 1. List
        $client = Client::factory()->create(['name' => 'Acme Corporation']);
        $this->actingAs($admin)->get(route('clients.index'))->assertOk()->assertSee('Acme Corporation');

        // 2. Validation: Required fields
        $response = $this->actingAs($admin)->from(route('clients.create'))->post(route('clients.store'), [
            'name' => '',
        ]);
        $response->assertSessionHasErrors(['name']);

        // 3. Store / Persistence
        $postData = [
            'name' => 'HouseFix Premium Client',
            'email' => 'client@housefix.test',
            'phone' => '9876543210',
            'address' => '123 Test Street',
            'city' => 'Chennai',
            'status' => 'active',
        ];
        $this->actingAs($admin)->post(route('clients.store'), $postData)->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'HouseFix Premium Client',
            'email' => 'client@housefix.test',
        ]);

        // 4. Update
        $createdClient = Client::query()->where('email', 'client@housefix.test')->first();
        $this->actingAs($admin)->put(route('clients.update', $createdClient), [
            'name' => 'HouseFix Updated Client',
            'phone' => '9876543211',
            'status' => 'active',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $createdClient->id,
            'name' => 'HouseFix Updated Client',
        ]);

        // 5. Delete
        $this->actingAs($admin)->delete(route('clients.destroy', $createdClient))->assertRedirect(route('clients.index'));
        $this->assertDatabaseMissing('clients', ['id' => $createdClient->id]);
    }

    /**
     * Test Project CRUD, Client Relationship & Progress.
     */
    public function test_project_crud_lifecycle_and_relationship(): void
    {
        $admin = $this->createSuperAdmin();
        $client = Client::factory()->create();

        // Store
        $projectData = [
            'project_code' => 'PRJ-99881',
            'client_id' => $client->id,
            'name' => 'Villa Construction Phase 1',
            'description' => 'Complete structural civil works',
            'type' => 'Civil',
            'priority' => 'high',
            'status' => 'active',
            'progress' => 25,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'location' => 'https://maps.google.com/?q=13.0827,80.2707',
        ];

        $response = $this->actingAs($admin)->post(route('projects.store'), $projectData);
        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'project_code' => 'PRJ-99881',
            'name' => 'Villa Construction Phase 1',
            'client_id' => $client->id,
        ]);

        $project = Project::query()->where('project_code', 'PRJ-99881')->first();
        $this->assertEquals($client->id, $project->client->id);
    }

    /**
     * Test Task Creation and Project Linking.
     */
    public function test_task_creation_and_project_linking(): void
    {
        $admin = $this->createSuperAdmin();
        $project = Project::factory()->create();

        $taskData = [
            'project_id' => $project->id,
            'title' => 'Electrical Conduit Laying',
            'description' => 'Install conduit pipes in slab',
            'type' => 'general',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => now()->addDays(5)->toDateString(),
        ];

        $this->actingAs($admin)->post(route('tasks.store'), $taskData)->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Electrical Conduit Laying',
        ]);
    }

    /**
     * Test Vendor CRUD.
     */
    public function test_vendor_crud(): void
    {
        $admin = $this->createSuperAdmin();

        $vendorData = [
            'name' => 'Supreme Cement Suppliers',
            'address' => 'Industrial Estate, Ambattur',
            'phone' => '9876543210',
        ];

        $this->actingAs($admin)->post(route('vendors.store'), $vendorData)->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', [
            'name' => 'Supreme Cement Suppliers',
        ]);
    }

    /**
     * Test Labour and LabourRole Relationship & Daily Rate.
     */
    public function test_labour_and_role_relationship(): void
    {
        $admin = $this->createSuperAdmin();
        $role = LabourRole::factory()->create([
            'name' => 'Mason Grade 1',
            'salary_type' => 'daily',
            'salary' => 950.00,
        ]);

        $labourData = [
            'name' => 'Murugan M',
            'labour_role_id' => $role->id,
            'phone_number' => '9944112233',
            'gender' => 'male',
            'salary' => 950.00,
        ];

        $this->actingAs($admin)->post(route('labours.store'), $labourData)->assertRedirect(route('labours.index'));

        $this->assertDatabaseHas('labours', [
            'name' => 'Murugan M',
            'labour_role_id' => $role->id,
        ]);
    }
}
