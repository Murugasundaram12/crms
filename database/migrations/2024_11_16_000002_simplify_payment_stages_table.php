<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_stages')) {
            return;
        }

        Schema::table('payment_stages', function (Blueprint $table) {
            if (Schema::hasColumn('payment_stages', 'stage_name') && ! Schema::hasColumn('payment_stages', 'name')) {
                $table->renameColumn('stage_name', 'name');
            }
            if (Schema::hasColumn('payment_stages', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
            foreach (['percentage', 'amount', 'status', 'order'] as $column) {
                if (Schema::hasColumn('payment_stages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_stages')) {
            return;
        }

        Schema::table('payment_stages', function (Blueprint $table) {
            if (Schema::hasColumn('payment_stages', 'name') && ! Schema::hasColumn('payment_stages', 'stage_name')) {
                $table->renameColumn('name', 'stage_name');
            }
            if (! Schema::hasColumn('payment_stages', 'project_id')) {
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
            }
            if (! Schema::hasColumn('payment_stages', 'percentage')) {
                $table->unsignedTinyInteger('percentage');
            }
            if (! Schema::hasColumn('payment_stages', 'amount')) {
                $table->decimal('amount', 14, 2)->nullable();
            }
            if (! Schema::hasColumn('payment_stages', 'status')) {
                $table->string('status')->default('pending');
            }
            if (! Schema::hasColumn('payment_stages', 'order')) {
                $table->unsignedInteger('order')->default(1);
            }
        });
    }
};
