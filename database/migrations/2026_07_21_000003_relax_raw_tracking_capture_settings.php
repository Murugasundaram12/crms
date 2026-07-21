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

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'max_accuracy_meters'],
            [
                'group' => 'tracking',
                'value' => '1000',
                'type' => 'integer',
                'description' => 'Maximum accepted GPS accuracy for storing raw tracking points. Timeline display uses a stricter filter.',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'minimum_distance_meters'],
            [
                'group' => 'tracking',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Minimum distance before the mobile app sends/saves a new point.',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')->where('key', 'max_accuracy_meters')->update([
            'value' => '50',
            'updated_at' => now(),
        ]);

        DB::table('app_settings')->where('key', 'minimum_distance_meters')->update([
            'value' => '25',
            'updated_at' => now(),
        ]);
    }
};
