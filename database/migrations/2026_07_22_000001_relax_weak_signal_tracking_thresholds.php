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
            'gps_max_accuracy_metres' => ['50', 'integer', 'Maximum GPS accuracy accepted for validated tracking and timeline route points.'],
            'timeline_max_accuracy_meters' => ['50', 'integer', 'Maximum GPS accuracy accepted while rendering employee tracking lines.'],
            'gps_max_inactive_gap_seconds' => ['3600', 'integer', 'Maximum gap between valid points before starting a separate tracking segment.'],
        ];

        foreach ($settings as $key => [$value, $type, $description]) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'group' => 'tracking',
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

        DB::table('app_settings')->where('key', 'gps_max_accuracy_metres')->update([
            'value' => '8',
            'updated_at' => now(),
        ]);

        DB::table('app_settings')->where('key', 'timeline_max_accuracy_meters')->update([
            'value' => '10',
            'updated_at' => now(),
        ]);

        DB::table('app_settings')->where('key', 'gps_max_inactive_gap_seconds')->update([
            'value' => '600',
            'updated_at' => now(),
        ]);
    }
};
