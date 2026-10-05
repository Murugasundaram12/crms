<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            if (! Schema::hasIndex(
                'employee_salaries',
                'employee_salaries_user_period_unique'
            )) {
                if (Schema::hasIndex(
                    'employee_salaries',
                    'employee_salaries_name_unique'
                )) {
                    $table->dropUnique('employee_salaries_name_unique');
                }

                $table->unique(
                    ['user_id', 'salary_period'],
                    'employee_salaries_user_period_unique'
                );
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            // IMPORTANT: create the new index FIRST.
            // The old index is currently required by the user_id foreign key.
            if (! Schema::hasIndex(
                'attendances',
                'attendances_user_date_unique'
            )) {
                $table->unique(
                    ['user_id', 'attendance_date'],
                    'attendances_user_date_unique'
                );
            }

            // Drop the old index only after the new index exists.
            if (Schema::hasIndex(
                'attendances',
                'attendances_user_id_attendance_date_index'
            )) {
                $table->dropIndex(
                    'attendances_user_id_attendance_date_index'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Rollback Limitations:
     * Re-applying the legacy `employee_salaries_name_unique` constraint requires
     * that all records in `employee_salaries` have distinct `name` values.
     * Once multiple salary cycles are generated for the same employee across months,
     * duplicate names will naturally and validly exist.
     *
     * In that case, re-adding `UNIQUE(name)` will be rejected by MySQL/MariaDB.
     * IMPORTANT: Production business data must NEVER be deleted or modified
     * solely to make a legacy rollback succeed.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // 1. Re-add the old index FIRST to satisfy foreign key requirements
            if (! Schema::hasIndex('attendances', 'attendances_user_id_attendance_date_index')) {
                $table->index(['user_id', 'attendance_date'], 'attendances_user_id_attendance_date_index');
            }

            // 2. Drop the unique index SECOND
            if (Schema::hasIndex('attendances', 'attendances_user_date_unique')) {
                $table->dropUnique('attendances_user_date_unique');
            }
        });

        Schema::table('employee_salaries', function (Blueprint $table) {
            if (Schema::hasIndex('employee_salaries', 'employee_salaries_user_period_unique')) {
                $table->dropUnique('employee_salaries_user_period_unique');
            }

            if (! Schema::hasIndex('employee_salaries', 'employee_salaries_name_unique')) {
                $table->unique('name', 'employee_salaries_name_unique');
            }
        });
    }
};
