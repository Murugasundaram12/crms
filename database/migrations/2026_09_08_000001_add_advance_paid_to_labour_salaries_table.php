<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: Marked as PENDING. Do not execute automatically.
     */
    public function up(): void
    {
        if (Schema::hasTable('labour_salaries') && ! Schema::hasColumn('labour_salaries', 'advance_paid')) {
            Schema::table('labour_salaries', function (Blueprint $table) {
                $table->boolean('advance_paid')->default(false)->after('advance_adjusted');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('labour_salaries') && Schema::hasColumn('labour_salaries', 'advance_paid')) {
            Schema::table('labour_salaries', function (Blueprint $table) {
                $table->dropColumn('advance_paid');
            });
        }
    }
};
