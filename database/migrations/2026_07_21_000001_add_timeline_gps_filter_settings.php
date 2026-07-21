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
            ['key' => 'timeline_minimum_distance_meters', 'value' => '3', 'type' => 'integer', 'description' => 'Minimum movement distance before a point is included in timeline polylines.'],
            ['key' => 'timeline_max_accuracy_meters', 'value' => '10', 'type' => 'integer', 'description' => 'Maximum GPS accuracy accepted for timeline polyline generation.'],
            ['key' => 'timeline_simplify_after_points', 'value' => '1000', 'type' => 'integer', 'description' => 'Simplify timeline polylines after this many filtered points.'],
            ['key' => 'timeline_simplification_tolerance_meters', 'value' => '8', 'type' => 'integer', 'description' => 'Douglas-Peucker simplification tolerance for timeline polylines.'],
            ['key' => 'timeline_bearing_drift_distance_meters', 'value' => '10', 'type' => 'integer', 'description' => 'Distance below which sharp bearing changes are treated as GPS drift.'],
            ['key' => 'timeline_bearing_change_degrees', 'value' => '60', 'type' => 'integer', 'description' => 'Bearing change threshold used to reject short GPS drift points.'],
            ['key' => 'timeline_max_bearing_change_degrees', 'value' => '170', 'type' => 'integer', 'description' => 'Maximum bearing change allowed between timeline points before treating the point as drift.'],
            ['key' => 'timeline_max_computed_speed_kmh', 'value' => '90', 'type' => 'integer', 'description' => 'Maximum computed speed allowed between timeline points before treating the point as a GPS jump.'],
            ['key' => 'gps_max_accuracy_metres', 'value' => '10', 'type' => 'integer', 'description' => 'Maximum GPS accuracy accepted before a location point is saved to the validated tracking timeline.'],
            ['key' => 'gps_min_distance_metres', 'value' => '3', 'type' => 'integer', 'description' => 'Minimum distance from the previous validated point required before saving a new route point.'],
            ['key' => 'gps_max_speed_mps', 'value' => '25', 'type' => 'integer', 'description' => 'Maximum computed or reported movement speed in metres per second before rejecting a GPS point.'],
            ['key' => 'gps_max_bearing_change_degrees', 'value' => '170', 'type' => 'integer', 'description' => 'Maximum reliable bearing change before rejecting a GPS point.'],
            ['key' => 'gps_bearing_min_distance_metres', 'value' => '10', 'type' => 'integer', 'description' => 'Minimum segment distance required before bearing validation is applied.'],
            ['key' => 'gps_max_inactive_gap_seconds', 'value' => '600', 'type' => 'integer', 'description' => 'Maximum inactive gap before timeline line segments are split.'],
        ];

        foreach ($settings as $setting) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => 'tracking',
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description'],
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

        DB::table('app_settings')->whereIn('key', [
            'timeline_minimum_distance_meters',
            'timeline_max_accuracy_meters',
            'timeline_simplify_after_points',
            'timeline_simplification_tolerance_meters',
            'timeline_bearing_drift_distance_meters',
            'timeline_bearing_change_degrees',
            'timeline_max_bearing_change_degrees',
            'timeline_max_computed_speed_kmh',
            'gps_max_accuracy_metres',
            'gps_min_distance_metres',
            'gps_max_speed_mps',
            'gps_max_bearing_change_degrees',
            'gps_bearing_min_distance_metres',
            'gps_max_inactive_gap_seconds',
        ])->delete();
    }
};
