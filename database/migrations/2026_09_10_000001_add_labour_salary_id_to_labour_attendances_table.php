<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('labour_attendances') && ! Schema::hasColumn('labour_attendances', 'labour_salary_id')) {
            Schema::table('labour_attendances', function (Blueprint $table) {
                $table->foreignId('labour_salary_id')
                    ->nullable()
                    ->after('notes')
                    ->constrained('labour_salaries')
                    ->nullOnDelete();
            });

            // Backfill existing paid attendances for already paid salaries
            $paidSalaries = DB::table('labour_salaries')->where('status', 'paid')->get();
            foreach ($paidSalaries as $salary) {
                DB::table('labour_attendances')
                    ->where('labour_id', $salary->labour_id)
                    ->whereNull('labour_salary_id')
                    ->whereBetween('attendance_date', [$salary->salary_period_start, $salary->salary_period_end])
                    ->where('created_at', '<=', $salary->created_at)
                    ->update(['labour_salary_id' => $salary->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('labour_attendances') && Schema::hasColumn('labour_attendances', 'labour_salary_id')) {
            Schema::table('labour_attendances', function (Blueprint $table) {
                $table->dropForeign(['labour_salary_id']);
                $table->dropColumn('labour_salary_id');
            });
        }
    }
};
