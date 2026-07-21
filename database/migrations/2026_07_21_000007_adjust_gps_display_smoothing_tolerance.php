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

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'gps_douglas_peucker_tolerance_metres'],
            [
                'group' => 'tracking',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Display-only Douglas-Peucker tolerance applied independently per employee tracking segment.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')
            ->where('key', 'gps_douglas_peucker_tolerance_metres')
            ->update(['value' => '3', 'updated_at' => now()]);
    }
};
