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
            ['key' => 'online_threshold_seconds'],
            [
                'group' => 'tracking',
                'value' => '1800',
                'type' => 'integer',
                'description' => 'Seconds since last device update before an employee is shown as offline.',
                'is_public' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')->where('key', 'online_threshold_seconds')->delete();
    }
};
