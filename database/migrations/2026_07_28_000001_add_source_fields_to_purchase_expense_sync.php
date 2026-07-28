<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('expenses', 'source_type')) {
                    $table->string('source_type', 50)->nullable();
                }
                if (! Schema::hasColumn('expenses', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('expense_transactions')) {
            Schema::table('expense_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('expense_transactions', 'source_type')) {
                    $table->string('source_type', 50)->nullable();
                }
                if (! Schema::hasColumn('expense_transactions', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_transactions')) {
            Schema::table('expense_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('expense_transactions', 'source_id')) {
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('expense_transactions', 'source_type')) {
                    $table->dropColumn('source_type');
                }
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'source_id')) {
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('expenses', 'source_type')) {
                    $table->dropColumn('source_type');
                }
            });
        }
    }
};
