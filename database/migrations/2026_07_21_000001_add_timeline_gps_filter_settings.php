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
            ['key' => 'timeline_minimum_distance_meters', 'value' => '10', 'type' => 'integer', 'description' => 'Minimum movement distance before a point is included in timeline polylines.'],
            ['key' => 'timeline_max_accuracy_meters', 'value' => '20', 'type' => 'integer', 'description' => 'Maximum GPS accuracy accepted for timeline polyline generation.'],
            ['key' => 'timeline_simplify_after_points', 'value' => '1000', 'type' => 'integer', 'description' => 'Simplify timeline polylines after this many filtered points.'],
            ['key' => 'timeline_simplification_tolerance_meters', 'value' => '8', 'type' => 'integer', 'description' => 'Douglas-Peucker simplification tolerance for timeline polylines.'],
            ['key' => 'timeline_bearing_drift_distance_meters', 'value' => '10', 'type' => 'integer', 'description' => 'Distance below which sharp bearing changes are treated as GPS drift.'],
            ['key' => 'timeline_bearing_change_degrees', 'value' => '60', 'type' => 'integer', 'description' => 'Bearing change threshold used to reject short GPS drift points.'],
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
        ])->delete();
    }
};
