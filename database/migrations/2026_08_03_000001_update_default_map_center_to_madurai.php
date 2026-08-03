<?php

use App\Support\CompanyMapDefaults;
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

        DB::table('app_settings')
            ->where('key', 'map_center_latitude')
            ->where('value', CompanyMapDefaults::OLD_COIMBATORE_LATITUDE)
            ->update([
                'value' => (string) CompanyMapDefaults::CENTER_LATITUDE,
                'updated_at' => now(),
            ]);

        DB::table('app_settings')
            ->where('key', 'map_center_longitude')
            ->where('value', CompanyMapDefaults::OLD_COIMBATORE_LONGITUDE)
            ->update([
                'value' => (string) CompanyMapDefaults::CENTER_LONGITUDE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')
            ->where('key', 'map_center_latitude')
            ->where('value', (string) CompanyMapDefaults::CENTER_LATITUDE)
            ->update([
                'value' => CompanyMapDefaults::OLD_COIMBATORE_LATITUDE,
                'updated_at' => now(),
            ]);

        DB::table('app_settings')
            ->where('key', 'map_center_longitude')
            ->where('value', (string) CompanyMapDefaults::CENTER_LONGITUDE)
            ->update([
                'value' => CompanyMapDefaults::OLD_COIMBATORE_LONGITUDE,
                'updated_at' => now(),
            ]);
    }
};
