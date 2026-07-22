<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet')) {
            return;
        }

        Schema::table('wallet', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('wallet', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('client_id');
            }
            if (! Schema::hasColumn('wallet', 'payment_mode')) {
                $table->integer('payment_mode')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('wallet', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('transfer_type');
            }
            if (! Schema::hasColumn('wallet', 'active_status')) {
                $table->boolean('active_status')->default(true)->after('current_time');
            }
            if (! Schema::hasColumn('wallet', 'delete_status')) {
                $table->boolean('delete_status')->default(false)->after('active_status');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE wallet MODIFY `current_date` DATETIME NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallet')) {
            return;
        }

        Schema::table('wallet', function (Blueprint $table) {
            foreach (['client_id', 'project_id', 'payment_mode', 'stage_id', 'active_status', 'delete_status'] as $column) {
                if (Schema::hasColumn('wallet', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
