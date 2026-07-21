<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\EmployeeDevice;
use App\Models\Employee;
use App\Models\LocationTracking;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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
                'leave_requests',
                'leave_types',
                'payments',
                'quotations',
                'expenses',
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
                'app_settings',
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

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->string('quotation_number')->nullable();
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
            $table->string('device_type')->nullable();
            $table->string('brand')->nullable();
            $table->string('board')->nullable();
            $table->string('sdk_version')->nullable();
            $table->string('model')->nullable();
            $table->text('messaging_token')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('bearing', 6, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_wifi_on')->default(false);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->string('signal_strength')->nullable();
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
            $table->decimal('bearing', 6, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->string('type')->default('travelling');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('project_id')->nullable();
            $table->integer('amount')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('leave_type_id')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
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

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            ['group' => 'mobile_app', 'key' => 'app_version', 'value' => '1.2.3', 'type' => 'string', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'tracking', 'key' => 'tracking_interval_seconds', 'value' => '45', 'type' => 'integer', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'tracking', 'key' => 'offline_tracking_enabled', 'value' => 'true', 'type' => 'boolean', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'attendance', 'key' => 'geofence_enabled', 'value' => 'true', 'type' => 'boolean', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'map', 'key' => 'map_center_latitude', 'value' => '10.5', 'type' => 'float', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'map', 'key' => 'map_center_longitude', 'value' => '77.5', 'type' => 'float', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'map', 'key' => 'map_zoom_level', 'value' => '14', 'type' => 'integer', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_login_allows_only_one_active_session_for_the_same_user(): void
    {
        $user = User::query()->create([
            'name' => 'Multi Session User',
            'email' => 'multi-session@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $firstLogin = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device One',
        ]);

        $secondLogin = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Device Two',
        ]);

        $firstLogin->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('active_tokens_count', 1);

        $secondLogin->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('active_tokens_count', 1);

        $this->assertSame(1, $user->mobileApiTokens()->whereNull('expires_at')->orWhere('expires_at', '>', now())->count());

        $this->withHeaders(['Authorization' => 'Bearer ' . $firstLogin->json('token')])
            ->getJson('/api/dashboard')
            ->assertUnauthorized();

        $this->withHeaders(['Authorization' => 'Bearer ' . $secondLogin->json('token')])
            ->getJson('/api/dashboard')
            ->assertOk();
    }

    public function test_mobile_login_registers_device_and_admin_panel_shows_login_logout_status(): void
    {
        $user = User::query()->create([
            'name' => 'Device Status User',
            'email' => 'device-status@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_id' => 'device-status-phone',
            'device_name' => 'Device Status Phone',
        ]);

        $login->assertOk()
            ->assertJsonPath('device.device_id', 'device-status-phone')
            ->assertJsonPath('device.device_name', 'Device Status Phone');

        $this->actingAs($user)
            ->get(route('device-management.index'))
            ->assertOk()
            ->assertSee('Device Status User')
            ->assertSee('Device Status Phone')
            ->assertSee(route('device-management.destroy', $login->json('device.id')))
            ->assertSee('Login');

        $this->withHeaders(['Authorization' => 'Bearer ' . $login->json('token')])
            ->postJson('/api/logout')
            ->assertOk();

        $this->actingAs($user)
            ->get(route('device-management.index'))
            ->assertOk()
            ->assertSee('Device Status User')
            ->assertSee('Logout');
    }

    public function test_device_management_can_delete_registered_device(): void
    {
        $user = User::query()->create([
            'name' => 'Delete Device User',
            'email' => 'delete-device@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $device = EmployeeDevice::query()->create([
            'employee_id' => $user->id,
            'device_id' => 'delete-phone',
            'device_name' => 'Delete Phone',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('device-management.destroy', $device))
            ->assertRedirect(route('device-management.index'))
            ->assertSessionHas('success', 'Device Delete Phone - delete-phone deleted successfully.');

        $this->assertDatabaseMissing('employee_devices', [
            'id' => $device->id,
        ]);
    }

    public function test_register_api_returns_admin_contact_message_when_device_is_already_registered(): void
    {
        $user = User::query()->create([
            'name' => 'Duplicate Device User',
            'email' => 'duplicate-device@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        EmployeeDevice::query()->create([
            'employee_id' => $user->id,
            'device_id' => 'duplicate-phone',
            'device_name' => 'Already Registered Phone',
            'last_seen_at' => now(),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Duplicate Device Login',
        ])->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer ' . $login->json('token')])
            ->postJson('/api/register', [
                'device_id' => 'duplicate-phone',
                'device_name' => 'Duplicate Phone Again',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'This device is already registered. Please contact admin.');

        $this->assertSame(1, EmployeeDevice::query()->where('device_id', 'duplicate-phone')->count());
    }

    public function test_field_management_device_api_aliases_verify_register_token_and_status_flow(): void
    {
        $user = User::query()->create([
            'name' => 'Field Device User',
            'email' => 'field-device@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Field Device Login',
        ])->assertOk();

        $headers = ['Authorization' => 'Bearer ' . $login->json('token')];

        $this->withHeaders($headers)
            ->postJson('/api/checkDevice', [
                'deviceId' => 'field-phone',
                'deviceType' => 'android',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'new')
            ->assertJsonPath('can_register', true);

        $this->withHeaders($headers)
            ->postJson('/api/registerDevice', [
                'deviceId' => 'field-phone',
                'deviceName' => 'Field Phone',
                'deviceType' => 'android',
                'brand' => 'Google',
                'board' => 'bluejay',
                'sdkVersion' => '35',
                'model' => 'Pixel 6a',
            ])
            ->assertCreated()
            ->assertJsonPath('device.device_id', 'field-phone')
            ->assertJsonPath('device.device_type', 'android')
            ->assertJsonPath('device.brand', 'Google')
            ->assertJsonPath('device.sdk_version', '35')
            ->assertJsonPath('device.model', 'Pixel 6a');

        $this->withHeaders($headers)
            ->postJson('/api/checkDevice', [
                'deviceId' => 'field-phone',
                'deviceType' => 'android',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'verified')
            ->assertJsonPath('can_register', false);

        $this->withHeaders($headers)
            ->postJson('/api/messagingToken', [
                'deviceId' => 'field-phone',
                'token' => 'fcm-token-123',
            ])
            ->assertOk()
            ->assertJsonPath('device.messaging_token', 'fcm-token-123');

        $this->withHeaders($headers)
            ->postJson('/api/updateDeviceStatus', [
                'deviceId' => 'field-phone',
                'latitude' => 11.016844,
                'longitude' => 76.955832,
                'accuracy' => 12,
                'speed' => 0,
                'activity' => 'ActivityType.STILL',
                'isGpsOn' => false,
                'isWifiOn' => true,
                'isMock' => false,
                'batteryPercentage' => 76,
                'signalStrength' => 'good',
            ])
            ->assertOk()
            ->assertJsonPath('device.latitude', 11.016844)
            ->assertJsonPath('device.is_gps_on', false)
            ->assertJsonPath('device.is_wifi_on', true)
            ->assertJsonPath('device.battery_percentage', 76)
            ->assertJsonPath('device.signal_strength', 'good');

        $this->withHeaders($headers)
            ->postJson('/api/checkDevice', ['deviceId' => 'another-phone'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Already registered with other device. Please contact admin.');
    }

    public function test_mobile_settings_routes_return_database_backed_values(): void
    {
        $this->getJson('/api/V1/getAppSettings')
            ->assertOk()
            ->assertJsonPath('data.app_version', '1.2.3')
            ->assertJsonPath('data.tracking_interval_seconds', 45);

        $this->getJson('/api/V1/getModuleSettings')
            ->assertOk()
            ->assertJsonPath('data.tracking.offline_tracking_enabled', true)
            ->assertJsonPath('data.attendance.geofence_enabled', true);

        $this->getJson('/api/V1/getMapSettings')
            ->assertOk()
            ->assertJsonPath('data.center_latitude', 10.5)
            ->assertJsonPath('data.center_longitude', 77.5)
            ->assertJsonPath('data.zoom_level', 14);
    }

    public function test_mobile_settings_are_enforced_by_attendance_and_tracking_apis(): void
    {
        $user = User::query()->create([
            'name' => 'Settings Guard User',
            'email' => 'settings-guard@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Settings Guard Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $trackingPayload = [
            'device_id' => 'settings-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 60,
            'isGpsOn' => true,
            'isMock' => false,
        ];

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'max_accuracy_meters'],
            ['group' => 'tracking', 'value' => '75', 'type' => 'integer', 'is_public' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        $this->withHeaders($headers)
            ->postJson('/api/check_in', $trackingPayload)
            ->assertCreated()
            ->assertJsonPath('tracking.accuracy', 60);

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'tracking_enabled'],
            ['group' => 'tracking', 'value' => 'false', 'type' => 'boolean', 'is_public' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        $this->withHeaders($headers)
            ->postJson('/api/tracking/location', $trackingPayload)
            ->assertForbidden();

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'check_out_enabled'],
            ['group' => 'attendance', 'value' => 'false', 'type' => 'boolean', 'is_public' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        $this->withHeaders($headers)
            ->postJson('/api/check_out', $trackingPayload)
            ->assertForbidden();
    }

    public function test_stationary_gps_drift_refreshes_status_without_inserting_tracking_point(): void
    {
        $user = User::query()->create([
            'name' => 'Stationary Drift User',
            'email' => 'stationary-drift@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Stationary Drift Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $initialPayload = [
            'device_id' => 'stationary-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 8,
            'speed' => 0,
            'activity' => 'still',
            'isGpsOn' => true,
            'isMock' => false,
            'batteryPercentage' => 71,
            'recorded_at' => '2026-07-21 10:00:00',
        ];

        $this->withHeaders($headers)
            ->postJson('/api/check_in', $initialPayload)
            ->assertCreated();

        $this->assertSame(1, LocationTracking::query()->where('employee_id', $user->id)->count());

        $driftPayload = array_merge($initialPayload, [
            'latitude' => 11.016854,
            'longitude' => 76.955842,
            'accuracy' => 19,
            'speed' => 0.2,
            'batteryPercentage' => 65,
            'recorded_at' => '2026-07-21 10:01:00',
            'type' => 'still',
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/tracking/location', $driftPayload)
            ->assertOk()
            ->assertJsonPath('inserted', false)
            ->assertJsonPath('tracking.latitude', 11.016844)
            ->assertJsonPath('tracking.longitude', 76.955832)
            ->assertJsonPath('tracking.accuracy', 19)
            ->assertJsonPath('tracking.battery_percentage', 65);

        $this->assertSame(1, LocationTracking::query()->where('employee_id', $user->id)->count());

        $device = \App\Models\EmployeeDevice::query()->where('employee_id', $user->id)->firstOrFail();
        $this->assertSame(11.016844, (float) $device->latitude);
        $this->assertSame(76.955832, (float) $device->longitude);
        $this->assertSame(19.0, (float) $device->accuracy);
    }

    public function test_poor_accuracy_tracking_update_does_not_overwrite_last_good_route_point(): void
    {
        $user = User::query()->create([
            'name' => 'Poor Accuracy User',
            'email' => 'poor-accuracy@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Poor Accuracy Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $initialPayload = [
            'device_id' => 'poor-accuracy-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 8,
            'speed' => 1,
            'activity' => 'travelling',
            'isGpsOn' => true,
            'isMock' => false,
            'batteryPercentage' => 71,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/check_in', $initialPayload)
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/tracking/location', array_merge($initialPayload, [
                'latitude' => 11.017500,
                'longitude' => 76.956500,
                'accuracy' => 80,
                'batteryPercentage' => 60,
            ]))
            ->assertOk()
            ->assertJsonPath('inserted', false);

        $this->assertSame(1, LocationTracking::query()->where('employee_id', $user->id)->count());
        $this->assertSame(8.0, (float) LocationTracking::query()->where('employee_id', $user->id)->firstOrFail()->accuracy);

        $device = \App\Models\EmployeeDevice::query()->where('employee_id', $user->id)->firstOrFail();
        $this->assertSame(11.016844, (float) $device->latitude);
        $this->assertSame(76.955832, (float) $device->longitude);
        $this->assertSame(80.0, (float) $device->accuracy);
    }

    public function test_missing_api_model_returns_clean_json_message(): void
    {
        $user = User::query()->create([
            'name' => 'Missing Model User',
            'email' => 'missing-model@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Missing Model Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/clients/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Client not found.');
    }

    public function test_all_missing_api_models_return_clean_json_messages(): void
    {
        $user = User::query()->create([
            'name' => 'All Missing Models User',
            'email' => 'all-missing-models@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'All Missing Models Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $cases = [
            ['GET', '/api/clients/999', 'Client not found.'],
            ['GET', '/api/projects/999', 'Project not found.'],
            ['GET', '/api/expenses/999', 'Expense not found.'],
            ['GET', '/api/payments/999', 'Payment not found.'],
            ['GET', '/api/tasks/999', 'Task not found.'],
            ['GET', '/api/employees/999', 'Employee not found.'],
            ['GET', '/api/admin/employees/999/timeline?date=2026-07-13', 'Employee not found.'],
            ['DELETE', '/api/leave-requests/999', 'LeaveRequest not found.'],
        ];

        foreach ($cases as [$method, $uri, $message]) {
            $response = match ($method) {
                'DELETE' => $this->withHeaders($headers)->deleteJson($uri),
                default => $this->withHeaders($headers)->getJson($uri),
            };

            $response->assertNotFound()
                ->assertJsonPath('message', $message);

            $this->assertStringNotContainsString('No query results', (string) $response->getContent());
        }
    }

    public function test_project_and_payment_options_work_when_quotations_total_amount_column_is_missing(): void
    {
        $user = User::query()->create([
            'name' => 'Quotation Fallback User',
            'email' => 'quotation-fallback@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $client = Client::query()->create(['name' => 'Fallback Client', 'status' => 'active']);
        $project = Project::query()->create([
            'name' => 'Fallback Project',
            'project_code' => 'QF-001',
            'client_id' => $client->id,
            'type' => 'residential',
            'status' => 'active',
            'advance_amt' => 0,
            'profit' => 0,
        ]);

        DB::table('quotations')->insert([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'quotation_number' => 'QF-QUOTE-001',
            'amount' => 12500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Quotation Fallback Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->getJson('/api/projects/' . $project->id)
            ->assertOk()
            ->assertJsonPath('project.budget', 12500);

        $this->withHeaders($headers)
            ->getJson('/api/payments/options')
            ->assertOk()
            ->assertJsonPath('quotations.0.amount', '12500.00');
    }

    public function test_wallet_options_are_available_to_transfer_list_users(): void
    {
        $user = User::query()->create([
            'name' => 'Wallet Options User',
            'email' => 'wallet-options@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $permission = Permission::query()->create([
            'name' => 'List Transfers',
            'key' => 'transfers-list',
        ]);

        $role = Role::query()->create([
            'name' => 'Wallet Viewer',
            'description' => 'Can view wallet data',
        ]);
        $role->permissions()->sync([$permission->id]);
        $user->roles()->sync([$role->id]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Wallet Options Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/wallet/options')
            ->assertOk()
            ->assertJsonPath('wallet_balance', 0)
            ->assertJsonPath('can_view_transfers', true);
    }

    public function test_wallet_options_are_available_to_authenticated_mobile_users(): void
    {
        $user = User::query()->create([
            'name' => 'Wallet Options Basic User',
            'email' => 'wallet-options-basic@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 250,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Wallet Options Basic Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/wallet/options')
            ->assertOk()
            ->assertJsonPath('wallet_balance', 250)
            ->assertJsonPath('can_view_transfers', false)
            ->assertJsonPath('can_create_transfer', false);
    }

    public function test_leave_request_requires_active_leave_type_from_options(): void
    {
        $user = User::query()->create([
            'name' => 'Leave Type User',
            'email' => 'leave-type@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        DB::table('leave_types')->insert([
            ['id' => 1, 'name' => 'Inactive Leave', 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Casual Leave', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Leave Type Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->getJson('/api/leave-requests/options')
            ->assertOk()
            ->assertJsonCount(1, 'leave_types')
            ->assertJsonPath('leave_types.0.id', 2);

        $this->withHeaders($headers)
            ->postJson('/api/leave-requests', [
                'leave_type_id' => 1,
                'from_date' => '2026-07-20',
                'to_date' => '2026-07-21',
                'remarks' => 'Personal leave',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.leave_type_id.0', 'Selected leave type is not available. Call /leave-requests/options and use an active leave type id.');

        $this->withHeaders($headers)
            ->postJson('/api/leave-requests', [
                'leave_type_id' => 2,
                'from_date' => '2026-07-20',
                'to_date' => '2026-07-21',
                'remarks' => 'Personal leave',
            ])
            ->assertCreated()
            ->assertJsonPath('leave_request.leave_type_id', 2);
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

        $permissions = collect(['roles-list', 'permissions-list', 'tasks-list'])
            ->map(fn(string $key) => Permission::query()->create([
                'name' => ucwords(str_replace('-', ' ', $key)),
                'key' => $key,
            ]));

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'description' => 'Full system access',
        ]);
        $role->permissions()->sync($permissions->pluck('id'));
        $user->roles()->sync([$role->id]);

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
        ])
            ->assertOk()
            ->assertJsonPath('user.roles.0.name', 'Super Admin')
            ->assertJsonMissingPath('user.permissions');

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
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonPath('tracking.type', 'checked_in');

        $this->withHeaders($headers)
            ->postJson('/api/tracking/location', $trackingPayload + ['type' => 'still'])
            ->assertOk()
            ->assertJsonPath('tracking.type', 'checked_in');

        $this->withHeaders($headers)
            ->postJson('/api/devices/live-status', $trackingPayload)
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/attendance?status=checked_in')
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/me/permissions')
            ->assertOk()
            ->assertJsonPath('roles.0.name', 'Super Admin')
            ->assertJsonPath('permissions.0', 'permissions-list');

        $this->withHeaders($headers)
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Super Admin')
            ->assertJsonMissingPath('data.0.permissions');

        $this->withHeaders($headers)
            ->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'permissions-list');

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
            ->assertOk()
            ->assertJsonPath('summary.raw_points_count', 1)
            ->assertJsonPath('summary.points_count', 1)
            ->assertJsonCount(1, 'polylinePoints');

        $this->withHeaders($headers)
            ->postJson('/api/check_out', $trackingPayload + ['notes' => 'Check out'])
            ->assertOk()
            ->assertJsonPath('tracking.type', 'checked_out');

        $this->withHeaders($headers)
            ->deleteJson('/api/tasks/' . $taskId)
            ->assertOk();

        $this->withHeaders($headers)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('location_trackings', [
            'employee_id' => $user->id,
            'type' => 'checked_in',
        ]);
        $this->assertDatabaseHas('location_trackings', [
            'employee_id' => $user->id,
            'type' => 'checked_out',
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

    public function test_attendance_status_api_reports_check_in_and_check_out_state(): void
    {
        $user = User::query()->create([
            'name' => 'Attendance Status User',
            'email' => 'attendance-status@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Attendance Status Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->getJson('/api/attendance/status')
            ->assertOk()
            ->assertJsonPath('status', 'checked_out')
            ->assertJsonPath('is_checked_in', false)
            ->assertJsonPath('can_check_in', true)
            ->assertJsonPath('can_check_out', false);

        $trackingPayload = [
            'device_id' => 'attendance-status-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 12,
            'isGpsOn' => true,
            'isMock' => false,
            'recorded_at' => '2026-07-13T03:30:00Z',
        ];

        Carbon::setTestNow(Carbon::parse('2026-07-15 11:36:00', 'Asia/Kolkata'));

        try {
            $checkInResponse = $this->withHeaders($headers)
                ->postJson('/api/check_in', $trackingPayload)
                ->assertCreated()
                ->assertJsonPath('attendance.check_in_time', '11:36 AM');

            $checkInTracking = LocationTracking::query()->find($checkInResponse->json('tracking.id'));
            $this->assertSame(
                '2026-07-15 11:36:00',
                $checkInTracking?->recorded_at?->copy()->timezone('Asia/Kolkata')->format('Y-m-d H:i:s')
            );

            $this->withHeaders($headers)
                ->getJson('/api/attendance/status')
                ->assertOk()
                ->assertJsonPath('status', 'checked_in')
                ->assertJsonPath('is_checked_in', true)
                ->assertJsonPath('can_check_in', false)
                ->assertJsonPath('can_check_out', true);

            Carbon::setTestNow(Carbon::parse('2026-07-15 11:38:00', 'Asia/Kolkata'));

            $this->withHeaders($headers)
                ->postJson('/api/check_out', $trackingPayload)
                ->assertOk()
                ->assertJsonPath('attendance.check_in_time', '11:36 AM')
                ->assertJsonPath('attendance.check_out_time', '11:38 AM')
                ->assertJsonPath('attendance.worked_minutes', 2)
                ->assertJsonPath('attendance.worked_duration', '2m');

            $this->withHeaders($headers)
                ->postJson('/api/check_in', $trackingPayload)
                ->assertStatus(409)
                ->assertJsonPath('message', 'You have already checked in today.');

            Carbon::setTestNow(Carbon::parse('2026-07-16 09:00:00', 'Asia/Kolkata'));

            $this->withHeaders($headers)
                ->postJson('/api/check_in', $trackingPayload)
                ->assertCreated()
                ->assertJsonPath('attendance.attendance_date', '2026-07-16');
        } finally {
            Carbon::setTestNow();
        }

        $this->withHeaders($headers)
            ->getJson('/api/attendance/status')
            ->assertOk()
            ->assertJsonPath('status', 'checked_in')
            ->assertJsonPath('is_checked_in', true)
            ->assertJsonPath('can_check_in', false)
            ->assertJsonPath('can_check_out', true);
    }

    public function test_attendance_status_reports_previous_day_open_check_in_as_active(): void
    {
        $user = User::query()->create([
            'name' => 'Overnight Attendance User',
            'email' => 'overnight-attendance@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        Attendance::query()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-07-15',
            'check_in_at' => Carbon::parse('2026-07-15 18:38:00', 'Asia/Kolkata'),
            'status' => 'present',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Overnight Attendance Test',
        ])->json('token');

        Carbon::setTestNow(Carbon::parse('2026-07-16 09:19:00', 'Asia/Kolkata'));

        try {
            $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->getJson('/api/attendance/status')
                ->assertOk()
                ->assertJsonPath('date', '2026-07-16')
                ->assertJsonPath('status', 'checked_in')
                ->assertJsonPath('is_checked_in', true)
                ->assertJsonPath('can_check_in', false)
                ->assertJsonPath('can_check_out', true)
                ->assertJsonPath('active_attendance.attendance_date', '2026-07-15')
                ->assertJsonPath('active_attendance.check_in_time', '06:38 PM');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_logout_is_blocked_when_today_due_task_is_not_completed(): void
    {
        $user = User::query()->create([
            'name' => 'Task Logout User',
            'email' => 'task-logout@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $task = Task::query()->create([
            'employee_id' => $employee->id,
            'title' => 'Finish today task',
            'description' => 'Must be completed before logout',
            'type' => 'general',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => now()->toDateString(),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Task Logout Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)
            ->postJson('/api/logout')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Due tasks are not completed. Complete due tasks before logout.')
            ->assertJsonPath('pending_tasks_count', 1)
            ->assertJsonPath('tasks.0.id', $task->id);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_check_out_is_blocked_only_for_incomplete_due_tasks_not_future_weekly_tasks(): void
    {
        $user = User::query()->create([
            'name' => 'Task Checkout User',
            'email' => 'task-checkout@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $employee = Employee::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $todayTask = Task::query()->create([
            'employee_id' => $employee->id,
            'title' => 'Today task',
            'description' => 'Must complete today',
            'type' => 'general',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => now()->toDateString(),
        ]);

        Task::query()->create([
            'employee_id' => $employee->id,
            'title' => 'Weekly task with time',
            'description' => 'Can be completed later this week',
            'type' => 'weekly',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Task Checkout Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];
        $trackingPayload = [
            'device_id' => 'task-checkout-device',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 12,
            'isGpsOn' => true,
            'isMock' => false,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/check_in', $trackingPayload)
            ->assertCreated();

        $this->withHeaders($headers)
            ->postJson('/api/check_out', $trackingPayload)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Due tasks are not completed. Complete due tasks before check-out.')
            ->assertJsonPath('pending_tasks_count', 1)
            ->assertJsonPath('tasks.0.id', $todayTask->id);

        $todayTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/check_out', $trackingPayload)
            ->assertOk()
            ->assertJsonPath('message', 'Checked out successfully.');
    }

    public function test_wallet_transfer_scopes_super_admin_and_employee_wallet_lists(): void
    {
        $actor = User::query()->create([
            'name' => 'Wallet Admin',
            'email' => 'wallet-admin@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'wallet' => 1500,
            'password' => Hash::make('password'),
        ]);

        $target = User::query()->create([
            'name' => 'Wallet Employee',
            'email' => 'wallet-employee@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $client = Client::query()->create([
            'name' => 'Wallet Client',
            'status' => 'active',
        ]);

        $project = Project::query()->create([
            'name' => 'Wallet Project',
            'project_code' => 'WAL-001',
            'client_id' => $client->id,
            'type' => 'residential',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $actor->email,
            'password' => 'password',
            'device_name' => 'Wallet Target Test',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        $payload = [
            'user_id' => $target->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'payment_mode' => 1,
            'current_date' => now()->toDateString(),
            'time' => '09:30',
        ];

        $this->withHeaders($headers)
            ->postJson('/api/wallet/transfer', $payload + [
                'amount' => 1000,
                'transfer_type' => 0,
                'description' => 'Employee advance',
            ])
            ->assertCreated()
            ->assertJsonPath('wallet.user_id', $target->id)
            ->assertJsonPath('wallet_balance', 1000)
            ->assertJsonPath('sender_wallet_balance', 500)
            ->assertJsonPath('counter_wallet.user_id', $actor->id)
            ->assertJsonPath('counter_wallet.transfer_type', 1);

        $this->assertEquals(500.0, (float) $actor->fresh()->wallet);
        $this->assertEquals(1000.0, (float) $target->fresh()->wallet);

        $this->withHeaders($headers)
            ->postJson('/api/wallet/transfer', $payload + [
                'amount' => 400,
                'transfer_type' => 1,
                'description' => 'Employee debit',
            ])
            ->assertCreated()
            ->assertJsonPath('wallet.user_id', $target->id)
            ->assertJsonPath('wallet_balance', 600);

        $this->withHeaders($headers)
            ->getJson('/api/wallet?user_id=' . $target->id)
            ->assertOk()
            ->assertJsonPath('credit_total', 1000)
            ->assertJsonPath('debit_total', 400)
            ->assertJsonPath('net_total', 600)
            ->assertJsonPath('total_amount', 600);

        $targetToken = $this->postJson('/api/login', [
            'email' => $target->email,
            'password' => 'password',
            'device_name' => 'Wallet Employee Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $targetToken])
            ->getJson('/api/wallet?user_id=' . $actor->id)
            ->assertOk()
            ->assertJsonPath('credit_total', 1000)
            ->assertJsonPath('debit_total', 400)
            ->assertJsonPath('net_total', 600)
            ->assertJsonPath('total_amount', 600);

        $this->withHeaders($headers)
            ->postJson('/api/wallet/transfer', $payload + [
                'amount' => 1000,
                'transfer_type' => 0,
                'description' => 'Too much advance',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'Amount is insufficient');
    }

    public function test_mobile_api_module_routes_allow_employee_own_views_only(): void
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
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->withHeaders(['Authorization' => 'Bearer ' . $loginResponse->json('token')])
            ->getJson('/api/attendance')
            ->assertOk();

        $this->withHeaders(['Authorization' => 'Bearer ' . $loginResponse->json('token')])
            ->getJson('/api/employees')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden.');
    }

    public function test_super_admin_sees_all_app_data_but_other_roles_are_scoped_to_own_data(): void
    {
        $admin = User::query()->create([
            'name' => 'Scope Admin',
            'email' => 'scope-admin@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $manager = User::query()->create([
            'name' => 'Scope Manager',
            'email' => 'scope-manager@example.com',
            'role' => 'Manager',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $employee = User::query()->create([
            'name' => 'Scope Employee',
            'email' => 'scope-employee@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $managerEmployee = Employee::query()->create([
            'id' => $manager->id,
            'name' => $manager->name,
            'email' => $manager->email,
            'role' => 'Manager',
            'status' => 'active',
        ]);

        $employeeRecord = Employee::query()->create([
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => 'Employee',
            'status' => 'active',
        ]);

        $client = Client::query()->create(['name' => 'Scope Client', 'status' => 'active']);
        $managerProject = Project::query()->create([
            'name' => 'Manager Project',
            'project_code' => 'MAN-SCOPE',
            'client_id' => $client->id,
            'type' => 'residential',
            'status' => 'active',
        ]);
        $employeeProject = Project::query()->create([
            'name' => 'Employee Project',
            'project_code' => 'EMP-SCOPE',
            'client_id' => $client->id,
            'type' => 'residential',
            'status' => 'active',
        ]);

        Task::query()->create([
            'project_id' => $managerProject->id,
            'employee_id' => $managerEmployee->id,
            'title' => 'Manager Task',
            'type' => 'daily',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        Task::query()->create([
            'project_id' => $employeeProject->id,
            'employee_id' => $employeeRecord->id,
            'title' => 'Employee Task',
            'type' => 'daily',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        Attendance::query()->create([
            'user_id' => $manager->id,
            'attendance_date' => '2026-07-16',
            'check_in_at' => '2026-07-16 09:00:00',
            'status' => 'present',
        ]);

        Attendance::query()->create([
            'user_id' => $employee->id,
            'attendance_date' => '2026-07-16',
            'check_in_at' => '2026-07-16 09:30:00',
            'status' => 'present',
        ]);

        $adminToken = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'Scope Admin',
        ])->json('token');

        $managerToken = $this->postJson('/api/login', [
            'email' => $manager->email,
            'password' => 'password',
            'device_name' => 'Scope Manager',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders(['Authorization' => 'Bearer ' . $adminToken])
            ->getJson('/api/attendance')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders(['Authorization' => 'Bearer ' . $managerToken])
            ->getJson('/api/tasks?employee_id=' . $employeeRecord->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_id', $managerEmployee->id);

        $this->withHeaders(['Authorization' => 'Bearer ' . $managerToken])
            ->getJson('/api/attendance?user_id=' . $employee->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $manager->id);
    }

    public function test_attendance_history_returns_only_logged_in_employee_records(): void
    {
        $actor = User::query()->create([
            'name' => 'Attendance Owner',
            'email' => 'attendance-owner@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $other = User::query()->create([
            'name' => 'Other Attendance User',
            'email' => 'other-attendance@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        $permission = Permission::query()->create([
            'name' => 'List Attendance',
            'key' => 'attendance-list',
        ]);

        $role = Role::query()->create([
            'name' => 'Attendance Admin',
            'description' => 'Can list attendance',
        ]);
        $role->permissions()->sync([$permission->id]);
        $actor->roles()->sync([$role->id]);

        Attendance::query()->create([
            'user_id' => $actor->id,
            'attendance_date' => now()->toDateString(),
            'check_in_at' => now()->subHours(2),
            'check_out_at' => now()->subHour(),
            'worked_minutes' => 60,
            'status' => 'present',
        ]);

        Attendance::query()->create([
            'user_id' => $other->id,
            'attendance_date' => now()->toDateString(),
            'check_in_at' => now()->subHours(3),
            'check_out_at' => now()->subHours(2),
            'worked_minutes' => 60,
            'status' => 'present',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $actor->email,
            'password' => 'password',
            'device_name' => 'Attendance History Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/attendance?page=1&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $actor->id);
    }

    public function test_attendance_history_displays_check_in_from_checkout_and_worked_minutes(): void
    {
        $user = User::query()->create([
            'name' => 'Attendance Time User',
            'email' => 'attendance-time@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'wallet' => 0,
            'password' => Hash::make('password'),
        ]);

        Attendance::query()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-07-15',
            'check_in_at' => Carbon::parse('2026-07-15 10:38:19', 'Asia/Kolkata'),
            'check_out_at' => Carbon::parse('2026-07-15 16:08:19', 'Asia/Kolkata'),
            'worked_minutes' => 2,
            'status' => 'present',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Attendance Time Test',
        ])->json('token');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/attendance?page=1&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.check_in_time', '04:06 PM')
            ->assertJsonPath('data.0.check_out_time', '04:08 PM')
            ->assertJsonPath('data.0.worked_minutes', 2)
            ->assertJsonPath('data.0.worked_duration', '2m');
    }

    private function assertSoftDeletedOrMissingTask(int $taskId): void
    {
        $this->assertFalse(Task::query()->whereKey($taskId)->exists());
    }
}
