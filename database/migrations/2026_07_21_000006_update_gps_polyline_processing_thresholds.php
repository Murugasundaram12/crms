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
            'gps_max_accuracy_metres' => ['8', 'integer', 'Maximum GPS accuracy accepted for validated tracking and timeline route points.'],
            'gps_min_distance_metres' => ['5', 'integer', 'Minimum movement distance; points at or below this distance are treated as drift for route drawing.'],
            'gps_max_speed_mps' => ['25', 'integer', 'Maximum computed movement speed in metres per second before rejecting a GPS point.'],
            'gps_max_bearing_change_degrees' => ['45', 'integer', 'Bearing change threshold for marking a movement candidate as suspicious before spike filtering.'],
            'gps_bearing_min_segment_distance_metres' => ['10', 'integer', 'Minimum segment distance required before bearing or angle validation is considered reliable.'],
            'gps_bearing_min_distance_metres' => ['10', 'integer', 'Legacy alias for minimum segment distance used in GPS bearing validation.'],
            'gps_douglas_peucker_tolerance_metres' => ['3', 'integer', 'Douglas-Peucker simplification tolerance applied independently per timeline polyline segment.'],
        ];

        foreach ($settings as $key => [$value, $type, $description]) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'group' => 'tracking',
                    'value' => $value,
                    'type' => $type,
                    'description' => $description,
                    'updated_at' => $now,
                    'created_at' => $now,
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
            'gps_bearing_min_segment_distance_metres',
            'gps_douglas_peucker_tolerance_metres',
        ])->delete();
    }
};
