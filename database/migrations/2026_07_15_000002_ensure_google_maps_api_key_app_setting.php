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
        $value = config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY', '')) ?: 'AIzaSyDNZMjI6BykptQrTCZJiPX2iEwBmd9UZUU';
        $existing = DB::table('app_settings')->where('key', 'google_maps_api_key')->first();

        if ($existing) {
            DB::table('app_settings')
                ->where('key', 'google_maps_api_key')
                ->update([
                    'group' => 'map',
                    'value' => filled($existing->value) ? $existing->value : $value,
                    'type' => 'string',
                    'description' => 'Google Maps JavaScript API key.',
                    'is_public' => true,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('app_settings')->insert([
            'group' => 'map',
            'key' => 'google_maps_api_key',
            'value' => $value,
            'type' => 'string',
            'description' => 'Google Maps JavaScript API key.',
            'is_public' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
    }

    public function down(): void
    {
        //
    }
};
