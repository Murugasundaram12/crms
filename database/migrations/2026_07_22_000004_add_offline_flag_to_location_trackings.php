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
            if (! Schema::hasColumn('location_trackings', 'is_offline')) {
                $table->boolean('is_offline')->default(false)->after('is_mock_location');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('location_trackings', 'is_offline')) {
                $table->dropColumn('is_offline');
            }
        });
    }
};
