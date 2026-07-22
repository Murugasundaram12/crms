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
            if (! Schema::hasColumn('location_trackings', 'client_uuid')) {
                $table->string('client_uuid')->nullable()->after('device_id');
                $table->index(['employee_id', 'device_id', 'client_uuid'], 'location_trackings_client_uuid_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('location_trackings', 'client_uuid')) {
                $table->dropIndex('location_trackings_client_uuid_idx');
                $table->dropColumn('client_uuid');
            }
        });
    }
};
