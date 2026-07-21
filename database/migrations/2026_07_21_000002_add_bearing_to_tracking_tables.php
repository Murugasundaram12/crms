<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_devices', 'bearing')) {
                $table->decimal('bearing', 6, 2)->nullable()->after('speed');
            }
        });

        Schema::table('location_trackings', function (Blueprint $table) {
            if (! Schema::hasColumn('location_trackings', 'bearing')) {
                $table->decimal('bearing', 6, 2)->nullable()->after('speed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            if (Schema::hasColumn('employee_devices', 'bearing')) {
                $table->dropColumn('bearing');
            }
        });

        Schema::table('location_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('location_trackings', 'bearing')) {
                $table->dropColumn('bearing');
            }
        });
    }
};
