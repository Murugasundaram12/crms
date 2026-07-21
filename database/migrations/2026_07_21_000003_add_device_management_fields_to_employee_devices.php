<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_devices', 'device_type')) {
                $table->string('device_type')->nullable()->after('device_name');
            }

            if (! Schema::hasColumn('employee_devices', 'brand')) {
                $table->string('brand')->nullable()->after('device_type');
            }

            if (! Schema::hasColumn('employee_devices', 'board')) {
                $table->string('board')->nullable()->after('brand');
            }

            if (! Schema::hasColumn('employee_devices', 'sdk_version')) {
                $table->string('sdk_version')->nullable()->after('board');
            }

            if (! Schema::hasColumn('employee_devices', 'model')) {
                $table->string('model')->nullable()->after('sdk_version');
            }

            if (! Schema::hasColumn('employee_devices', 'messaging_token')) {
                $table->text('messaging_token')->nullable()->after('model');
            }

            if (! Schema::hasColumn('employee_devices', 'is_wifi_on')) {
                $table->boolean('is_wifi_on')->default(false)->after('is_gps_on');
            }

            if (! Schema::hasColumn('employee_devices', 'signal_strength')) {
                $table->string('signal_strength')->nullable()->after('battery_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            foreach ([
                'signal_strength',
                'is_wifi_on',
                'messaging_token',
                'model',
                'sdk_version',
                'board',
                'brand',
                'device_type',
            ] as $column) {
                if (Schema::hasColumn('employee_devices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
