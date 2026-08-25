<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_salaries')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_salaries', 'monthly_salary')) {
                    $table->decimal('monthly_salary', 12, 2)->default(0)->after('salary_amount');
                }
                if (! Schema::hasColumn('employee_salaries', 'working_days')) {
                    $table->unsignedInteger('working_days')->default(0)->after('monthly_salary');
                }
                if (! Schema::hasColumn('employee_salaries', 'present_days')) {
                    $table->decimal('present_days', 5, 2)->default(0)->after('working_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'half_days')) {
                    $table->unsignedInteger('half_days')->default(0)->after('present_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'paid_leave_days')) {
                    $table->unsignedInteger('paid_leave_days')->default(0)->after('half_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'unpaid_leave_days')) {
                    $table->unsignedInteger('unpaid_leave_days')->default(0)->after('paid_leave_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'absent_days')) {
                    $table->unsignedInteger('absent_days')->default(0)->after('unpaid_leave_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'per_day_salary')) {
                    $table->decimal('per_day_salary', 12, 2)->default(0)->after('absent_days');
                }
                if (! Schema::hasColumn('employee_salaries', 'gross_salary')) {
                    $table->decimal('gross_salary', 12, 2)->default(0)->after('per_day_salary');
                }
                if (! Schema::hasColumn('employee_salaries', 'half_day_deduction')) {
                    $table->decimal('half_day_deduction', 12, 2)->default(0)->after('gross_salary');
                }
                if (! Schema::hasColumn('employee_salaries', 'unpaid_leave_deduction')) {
                    $table->decimal('unpaid_leave_deduction', 12, 2)->default(0)->after('half_day_deduction');
                }
                if (! Schema::hasColumn('employee_salaries', 'absent_deduction')) {
                    $table->decimal('absent_deduction', 12, 2)->default(0)->after('unpaid_leave_deduction');
                }
                if (! Schema::hasColumn('employee_salaries', 'attendance_deduction')) {
                    $table->decimal('attendance_deduction', 12, 2)->default(0)->after('absent_deduction');
                }
                if (! Schema::hasColumn('employee_salaries', 'other_deductions')) {
                    $table->decimal('other_deductions', 12, 2)->default(0)->after('attendance_deduction');
                }
                if (! Schema::hasColumn('employee_salaries', 'overtime_amount')) {
                    $table->decimal('overtime_amount', 12, 2)->default(0)->after('other_deductions');
                }
                if (! Schema::hasColumn('employee_salaries', 'net_salary')) {
                    $table->decimal('net_salary', 12, 2)->default(0)->after('overtime_amount');
                }
                if (! Schema::hasColumn('employee_salaries', 'calculated_at')) {
                    $table->timestamp('calculated_at')->nullable()->after('net_salary');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_salaries')) {
            Schema::table('employee_salaries', function (Blueprint $table) {
                $columns = [
                    'monthly_salary',
                    'working_days',
                    'present_days',
                    'half_days',
                    'paid_leave_days',
                    'unpaid_leave_days',
                    'absent_days',
                    'per_day_salary',
                    'gross_salary',
                    'half_day_deduction',
                    'unpaid_leave_deduction',
                    'absent_deduction',
                    'attendance_deduction',
                    'other_deductions',
                    'overtime_amount',
                    'net_salary',
                    'calculated_at',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('employee_salaries', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
