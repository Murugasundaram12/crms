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
                if (! Schema::hasColumn('employee_salaries', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('employee_salaries', 'salary_period')) {
                    $table->string('salary_period', 50)->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('employee_salaries', 'salary_amount')) {
                    $table->decimal('salary_amount', 12, 2)->default(0)->after('salary_period');
                }
                if (! Schema::hasColumn('employee_salaries', 'paid_amount')) {
                    $table->decimal('paid_amount', 12, 2)->default(0)->after('salary_amount');
                }
                if (! Schema::hasColumn('employee_salaries', 'remaining_amount')) {
                    $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
                }
                if (! Schema::hasColumn('employee_salaries', 'payment_date')) {
                    $table->date('payment_date')->nullable()->after('remaining_amount');
                }
                if (! Schema::hasColumn('employee_salaries', 'payment_method_id')) {
                    $table->foreignId('payment_method_id')->nullable()->after('payment_date')->constrained('payment_methods')->nullOnDelete();
                }
                if (! Schema::hasColumn('employee_salaries', 'notes')) {
                    $table->text('notes')->nullable()->after('payment_method_id');
                }
                if (! Schema::hasColumn('employee_salaries', 'status')) {
                    $table->string('status', 30)->default('paid')->after('notes');
                }
                if (! Schema::hasColumn('employee_salaries', 'paid_by')) {
                    $table->foreignId('paid_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Safe backward-compatible migration
    }
};
