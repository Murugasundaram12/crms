<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\PaymentStage;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileApiSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            [
                'wallet',
                'location_trackings',
                'employee_devices',
                'attendances',
                'tasks',
                'payment_stages',
                'projects',
                'clients',
                'employees',
                'mobile_api_tokens',
                'user_roles',
                'role_permission',
                'permissions',
                'roles',
                'users',
            ] as $table
        ) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('designation')->nullable();
            $table->string('role')->nullable();
            $table->string('address')->nullable();
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->date('hire_date')->nullable();
            $table->string('status')->default('active');
            $table->decimal('wallet', 14, 2)->default(0);
            $table->string('avatar')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('mobile_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('mobile');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('designation')->nullable();
            $table->string('role')->default('Site Engineer');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('type');
            $table->string('status')->default('planning');
            $table->decimal('advance_amt', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payment_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('stage_name');
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('general');
            $table->boolean('auto_repeat')->default(false);
            $table->foreignId('recurring_source_id')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->boolean('is_important')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'device_id']);
        });

        Schema::create('location_trackings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->string('type')->default('travelling');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('wallet', function (Blueprint $table): void {
            $table->id();
            $table->integer('amount');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('project_id');
            $table->timestamp('current_date')->nullable();
            $table->text('description')->nullable();
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->integer('payment_mode');
            $table->integer('stage_id')->nullable();
            $table->integer('transfer_type')->default(0);
            $table->timestamps();
        });
    }

    public function test_mobile_api_routes_work_with_web_flow_payloads(): void
    {
        $user = User::query()->create([
            'name' => 'API User',
            'email' => 'api-user@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 1000,
            'password' => Hash::make('password'),
        ]);

        $employee = Employee::query()->create([
            'name' => 'API Employee',
            'email' => 'api-employee@example.com',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'API Client',
            'status' => 'active',
        ]);

        $project = Project::query()->create([
            'name' => 'API Project',
            'project_code' => 'API-001',
            'client_id' => $client->id,
            'type' => 'residential',
            'status' => 'active',
        ]);

        $stageId = DB::table('payment_stages')->insertGetId([
            'project_id' => $project->id,
            'stage_name' => 'Advance',
            'percentage' => 10,
            'amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stage = PaymentStage::query()->findOrFail($stageId);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Smoke Test',
        ])->assertOk();

        $token = $loginResponse->json('token');
        $headers = ['Authorization' => 'Bearer ' . $token];
        $trackingPayload = [
            'device_id' => 'test-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 12,
            'speed' => 0,
            'activity' => 'still',
            'isGpsOn' => true,
            'isMock' => false,
            'batteryPercentage' => 80,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/register', ['device_id' => 'test-device', 'device_name' => 'Test Phone'])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/check_in', $trackingPayload + ['notes' => 'Check in'])
            ->assertCreated()
            ->assertJsonPath('attendance.status', 'present');

        $this->withHeaders($headers)
            ->postJson('/api/tracking/location', $trackingPayload + ['type' => 'still'])
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/devices/live-status', $trackingPayload)
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/attendance?status=checked_in')
            ->assertOk();

        $taskPayload = [
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'title' => 'API Task',
            'description' => 'Smoke task',
            'type' => 'general',
            'auto_repeat' => false,
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => now()->toDateString(),
            'estimated_hours' => 2,
            'logged_hours' => 0,
            'is_important' => false,
            'sort_order' => 0,
        ];

        $taskId = $this->withHeaders($headers)
            ->postJson('/api/tasks/assign', $taskPayload)
            ->assertCreated()
            ->json('task.id');

        $this->withHeaders($headers)->getJson('/api/tasks')->assertOk();
        $this->withHeaders($headers)->getJson('/api/tasks/' . $taskId)->assertOk();

        $this->withHeaders($headers)
            ->putJson('/api/tasks/' . $taskId, array_merge($taskPayload, ['status' => 'completed']))
            ->assertOk()
            ->assertJsonPath('task.status', 'completed');

        $this->withHeaders($headers)
            ->getJson('/api/wallet/options')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/wallet/store', [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'amount' => 100,
                'payment_mode' => 3,
                'transfer_type' => 0,
                'stage_id' => $stage->id,
                'description' => 'API wallet entry',
                'current_date' => now()->toDateString(),
                'time' => '10:30',
            ])
            ->assertCreated()
            ->assertJsonPath('wallet.transfer_type', 0);

        $this->withHeaders($headers)->getJson('/api/wallet')->assertOk();
        $this->withHeaders($headers)->getJson('/api/employees/track')->assertOk();
        $this->withHeaders($headers)->getJson('/api/admin/employees/live-locations')->assertOk();
        $this->withHeaders($headers)
            ->getJson('/api/admin/employees/' . $user->id . '/timeline?date=' . now()->toDateString())
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/check_out', $trackingPayload + ['notes' => 'Check out'])
            ->assertOk();

        $this->withHeaders($headers)
            ->deleteJson('/api/tasks/' . $taskId)
            ->assertOk();

        $this->withHeaders($headers)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('wallet', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'amount' => 100,
        ]);
        $this->assertSoftDeletedOrMissingTask($taskId);
    }

    public function test_live_status_requires_an_active_attendance_session(): void
    {
        $user = User::query()->create([
            'name' => 'Live Status User',
            'email' => 'live-status@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Live Status Test',
        ])->assertOk();

        $token = $loginResponse->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/devices/live-status', [
                'device_id' => 'test-device',
                'latitude' => 11.016844,
                'longitude' => 76.955832,
                'accuracy' => 12,
                'speed' => 0,
                'activity' => 'still',
                'isGpsOn' => true,
                'isMock' => false,
                'batteryPercentage' => 80,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'No active attendance found. Tracking is allowed only after check-in and before check-out.');
    }

    public function test_mobile_api_module_routes_require_matching_web_permissions(): void
    {
        $user = User::query()->create([
            'name' => 'Restricted User',
            'email' => 'restricted-api@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Restricted Test',
        ])->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer ' . $loginResponse->json('token')])
            ->getJson('/api/tasks')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden.');
    }

    private function assertSoftDeletedOrMissingTask(int $taskId): void
    {
        $this->assertFalse(Task::query()->whereKey($taskId)->exists());
    }
}
