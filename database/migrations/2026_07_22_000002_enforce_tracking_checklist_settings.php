<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location_trackings')) {
            Schema::table('location_trackings', function (Blueprint $table) {
                if (! Schema::hasColumn('location_trackings', 'is_wifi_on')) {
                    $table->boolean('is_wifi_on')->default(false)->after('is_gps_on');
                }

                if (! Schema::hasColumn('location_trackings', 'signal_strength')) {
                    $table->string('signal_strength')->nullable()->after('battery_percentage');
                }
            });
        }

        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $now = now();
        $settings = [
            'minimum_distance_meters' => ['30', 'integer', 'Minimum distance before the mobile app sends/saves a new movement point.'],
            'gps_min_distance_metres' => ['30', 'integer', 'Minimum distance from the previous accepted tracking point before saving route movement.'],
            'timeline_minimum_distance_meters' => ['30', 'integer', 'Minimum movement distance before a point is included in employee tracking route lines.'],
            'gps_max_accuracy_metres' => ['50', 'integer', 'Maximum GPS accuracy accepted for validated tracking and timeline route points.'],
            'timeline_max_accuracy_meters' => ['50', 'integer', 'Maximum GPS accuracy accepted while rendering employee tracking lines.'],
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
        if (Schema::hasTable('location_trackings')) {
            Schema::table('location_trackings', function (Blueprint $table) {
                if (Schema::hasColumn('location_trackings', 'signal_strength')) {
                    $table->dropColumn('signal_strength');
                }

                if (Schema::hasColumn('location_trackings', 'is_wifi_on')) {
                    $table->dropColumn('is_wifi_on');
                }
            });
        }

        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')->where('key', 'minimum_distance_meters')->update([
            'value' => '5',
            'updated_at' => now(),
        ]);

        DB::table('app_settings')->where('key', 'gps_min_distance_metres')->update([
            'value' => '5',
            'updated_at' => now(),
        ]);

        DB::table('app_settings')->where('key', 'timeline_minimum_distance_meters')->update([
            'value' => '3',
            'updated_at' => now(),
        ]);
    }
};
