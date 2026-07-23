<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $now = now();
        $settings = [
            ['tracking', 'tracking_enabled', 'true', 'boolean', 'Enable employee tracking.'],
            ['tracking', 'live_location_enabled', 'true', 'boolean', 'Enable live location views.'],
            ['tracking', 'timeline_enabled', 'true', 'boolean', 'Enable employee tracking timeline.'],
            ['tracking', 'location_update_interval', '15', 'integer', 'Mobile GPS update interval value.'],
            ['tracking', 'location_update_interval_type', 'seconds', 'string', 'Mobile GPS update interval unit.'],
            ['tracking', 'tracking_interval_seconds', '15', 'integer', 'Computed mobile GPS update interval in seconds.'],
            ['tracking', 'background_tracking_required', 'true', 'boolean', 'Require background tracking after check-in.'],
            ['tracking', 'minimum_accuracy', '50', 'float', 'Maximum accepted GPS accuracy in meters.'],
            ['tracking', 'max_accuracy_meters', '50', 'float', 'Maximum raw GPS accuracy accepted by mobile API.'],
            ['tracking', 'gps_max_accuracy_metres', '50', 'float', 'Maximum GPS accuracy accepted by validator.'],
            ['tracking', 'timeline_max_accuracy_meters', '50', 'float', 'Maximum GPS accuracy accepted by timeline.'],
            ['tracking', 'minimum_distance_meters', '30', 'float', 'Minimum movement distance in meters.'],
            ['tracking', 'gps_min_distance_metres', '30', 'float', 'Minimum validator movement distance in meters.'],
            ['tracking', 'timeline_minimum_distance_meters', '30', 'float', 'Minimum timeline movement distance in meters.'],
            ['tracking', 'maximum_speed_kmph', '120', 'float', 'Maximum allowed GPS speed in km/h.'],
            ['tracking', 'gps_max_speed_mps', '33.333333', 'float', 'Maximum allowed GPS speed in m/s.'],
            ['tracking', 'timeline_max_computed_speed_kmh', '120', 'float', 'Maximum timeline computed speed in km/h.'],
            ['tracking', 'mock_location_allowed', 'false', 'boolean', 'Allow mock locations.'],
            ['tracking', 'offline_tracking_enabled', 'true', 'boolean', 'Allow offline queue sync.'],
            ['tracking', 'allow_sync_after_checkout', 'true', 'boolean', 'Allow queued points to sync after checkout if recorded inside attendance time.'],
            ['tracking', 'max_offline_sync_age_hours', '72', 'integer', 'Maximum offline sync age in hours.'],
            ['tracking', 'bulk_upload_limit', '100', 'integer', 'Maximum mobile bulk upload points.'],
            ['map', 'map_provider', 'google', 'string', 'Map provider.'],
            ['map', 'map_zoom_level', '12', 'integer', 'Default map zoom level.'],
            ['map', 'distance_unit', 'km', 'string', 'Distance display unit.'],
            ['tracking', 'default_route_mode', 'actual', 'string', 'Default route mode.'],
            ['tracking', 'actual_gps_route_enabled', 'true', 'boolean', 'Enable Actual GPS route mode.'],
            ['tracking', 'road_route_enabled', 'true', 'boolean', 'Enable Estimated Road route mode.'],
            ['tracking', 'show_offline_points', 'true', 'boolean', 'Show offline synced points.'],
            ['tracking', 'show_low_signal_points', 'true', 'boolean', 'Show low signal points.'],
            ['tracking', 'show_gaps', 'true', 'boolean', 'Show route gap indicators.'],
            ['tracking', 'offline_check_time', '15', 'integer', 'Online/offline check value.'],
            ['tracking', 'offline_check_time_type', 'minutes', 'string', 'Online/offline check unit.'],
            ['tracking', 'online_threshold_seconds', '900', 'integer', 'Computed online/offline threshold in seconds.'],
            ['tracking', 'large_gap_minutes', '10', 'float', 'Large route gap time threshold.'],
            ['tracking', 'gps_max_inactive_gap_seconds', '600', 'integer', 'Computed inactive route gap threshold in seconds.'],
            ['tracking', 'large_gap_distance_meters', '2000', 'float', 'Large route gap distance threshold.'],
            ['tracking', 'low_signal_threshold', '2', 'integer', 'Low signal warning threshold.'],
            ['tracking', 'show_ignored_reason_count', 'true', 'boolean', 'Show ignored GPS reason count in debug metrics.'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $description]) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'group' => $group,
                    'value' => $value,
                    'type' => $type,
                    'description' => $description,
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')
            ->whereIn('key', [
                'live_location_enabled',
                'timeline_enabled',
                'location_update_interval',
                'location_update_interval_type',
                'background_tracking_required',
                'minimum_accuracy',
                'maximum_speed_kmph',
                'allow_sync_after_checkout',
                'max_offline_sync_age_hours',
                'bulk_upload_limit',
                'map_provider',
                'distance_unit',
                'default_route_mode',
                'actual_gps_route_enabled',
                'road_route_enabled',
                'show_offline_points',
                'show_low_signal_points',
                'show_gaps',
                'offline_check_time',
                'offline_check_time_type',
                'large_gap_minutes',
                'large_gap_distance_meters',
                'low_signal_threshold',
                'show_ignored_reason_count',
            ])
            ->delete();
    }
};
