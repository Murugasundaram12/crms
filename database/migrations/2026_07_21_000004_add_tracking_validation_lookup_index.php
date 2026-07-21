<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table) {
            $table->index(['employee_id', 'device_id', 'recorded_at'], 'location_trackings_employee_device_recorded_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table) {
            $table->dropIndex('location_trackings_employee_device_recorded_idx');
        });
    }
};
