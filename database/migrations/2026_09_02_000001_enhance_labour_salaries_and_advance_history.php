<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('labour_salaries') && ! Schema::hasColumn('labour_salaries', 'advance_adjusted')) {
            Schema::table('labour_salaries', function (Blueprint $table) {
                $table->decimal('advance_adjusted', 12, 2)->default(0)->after('paid_amount');
            });
        }

        if (Schema::hasTable('advance_history') && ! Schema::hasColumn('advance_history', 'labour_salary_id')) {
            Schema::table('advance_history', function (Blueprint $table) {
                $table->foreignId('labour_salary_id')->nullable()->after('labour_expense_transaction_id')->constrained('labour_salaries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('advance_history') && Schema::hasColumn('advance_history', 'labour_salary_id')) {
            Schema::table('advance_history', function (Blueprint $table) {
                $table->dropForeign(['labour_salary_id']);
                $table->dropColumn('labour_salary_id');
            });
        }

        if (Schema::hasTable('labour_salaries') && Schema::hasColumn('labour_salaries', 'advance_adjusted')) {
            Schema::table('labour_salaries', function (Blueprint $table) {
                $table->dropColumn('advance_adjusted');
            });
        }
    }
};
