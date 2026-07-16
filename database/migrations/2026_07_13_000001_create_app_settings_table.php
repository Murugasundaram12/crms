<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        $now = now();
        $settings = [
            ['group' => 'mobile_app', 'key' => 'app_version', 'value' => '1.0.0', 'type' => 'string', 'description' => 'Current mobile app version.'],
            ['group' => 'mobile_app', 'key' => 'minimum_supported_version', 'value' => '1.0.0', 'type' => 'string', 'description' => 'Minimum allowed mobile app version.'],
            ['group' => 'mobile_app', 'key' => 'force_update', 'value' => 'false', 'type' => 'boolean', 'description' => 'Force users to update old app versions.'],
            ['group' => 'mobile_app', 'key' => 'privacy_policy_url', 'value' => '', 'type' => 'string', 'description' => 'Privacy policy URL shown in mobile app.'],
            ['group' => 'mobile_app', 'key' => 'attendance_time_type', 'value' => 'server_time', 'type' => 'string', 'description' => 'server_time or device_time.'],
            ['group' => 'tracking', 'key' => 'tracking_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable employee location tracking.'],
            ['group' => 'tracking', 'key' => 'tracking_interval_seconds', 'value' => '3', 'type' => 'integer', 'description' => 'Background location update interval.'],
            ['group' => 'tracking', 'key' => 'minimum_distance_meters', 'value' => '25', 'type' => 'integer', 'description' => 'Minimum distance before saving a new point.'],
            ['group' => 'tracking', 'key' => 'max_accuracy_meters', 'value' => '50', 'type' => 'integer', 'description' => 'Maximum accepted GPS accuracy.'],
            ['group' => 'tracking', 'key' => 'mock_location_allowed', 'value' => 'false', 'type' => 'boolean', 'description' => 'Allow mock locations.'],
            ['group' => 'tracking', 'key' => 'history_retention_days', 'value' => '90', 'type' => 'integer', 'description' => 'Tracking history retention days.'],
            ['group' => 'tracking', 'key' => 'offline_tracking_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow offline queue and sync.'],
            ['group' => 'tracking', 'key' => 'online_threshold_seconds', 'value' => '1800', 'type' => 'integer', 'description' => 'Seconds since last device update before an employee is shown as offline.'],
            ['group' => 'attendance', 'key' => 'attendance_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable attendance module.'],
            ['group' => 'attendance', 'key' => 'check_in_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable check-in.'],
            ['group' => 'attendance', 'key' => 'check_out_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable check-out.'],
            ['group' => 'attendance', 'key' => 'geofence_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require geofence attendance.'],
            ['group' => 'attendance', 'key' => 'geofence_radius_meters', 'value' => '100', 'type' => 'integer', 'description' => 'Allowed geofence radius.'],
            ['group' => 'attendance', 'key' => 'qr_attendance_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require QR attendance.'],
            ['group' => 'attendance', 'key' => 'ip_attendance_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Require IP based attendance.'],
            ['group' => 'attendance', 'key' => 'allowed_attendance_ips', 'value' => '[]', 'type' => 'json', 'description' => 'Allowed IP list for attendance.'],
            ['group' => 'modules', 'key' => 'tasks_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable tasks module.'],
            ['group' => 'modules', 'key' => 'expenses_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable expenses module.'],
            ['group' => 'modules', 'key' => 'wallet_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable wallet module.'],
            ['group' => 'modules', 'key' => 'leave_requests_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable leave requests module.'],
            ['group' => 'map', 'key' => 'map_center_latitude', 'value' => '11.016844', 'type' => 'float', 'description' => 'Dashboard map center latitude.'],
            ['group' => 'map', 'key' => 'map_center_longitude', 'value' => '76.955832', 'type' => 'float', 'description' => 'Dashboard map center longitude.'],
            ['group' => 'map', 'key' => 'map_zoom_level', 'value' => '12', 'type' => 'integer', 'description' => 'Dashboard map zoom level.'],
            ['group' => 'map', 'key' => 'google_maps_api_key', 'value' => 'AIzaSyDNZMjI6BykptQrTCZJiPX2iEwBmd9UZUU', 'type' => 'string', 'description' => 'Google Maps JavaScript API key.'],
        ];

        DB::table('app_settings')->insert(array_map(
            fn(array $setting): array => $setting + ['is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            $settings
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
