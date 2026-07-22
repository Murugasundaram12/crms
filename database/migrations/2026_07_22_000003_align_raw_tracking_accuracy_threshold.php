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
            ['key' => 'max_accuracy_meters'],
            [
                'group' => 'tracking',
                'value' => '50',
                'type' => 'integer',
                'description' => 'Maximum accepted GPS accuracy before accepting mobile tracking payloads.',
                'is_public' => true,
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

        DB::table('app_settings')->where('key', 'max_accuracy_meters')->update([
            'value' => '1000',
            'updated_at' => now(),
        ]);
    }
};
