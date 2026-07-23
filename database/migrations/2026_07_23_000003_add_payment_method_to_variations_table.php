<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('variations')) {
            Schema::table('variations', function (Blueprint $table) {
                if (! Schema::hasColumn('variations', 'payment_method_id')) {
                    $table->foreignId('payment_method_id')->nullable()->after('amount')->constrained('payment_methods')->nullOnDelete();
                }
                if (! Schema::hasColumn('variations', 'employee_id')) {
                    $table->foreignId('employee_id')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('variations')) {
            Schema::table('variations', function (Blueprint $table) {
                if (Schema::hasColumn('variations', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
                if (Schema::hasColumn('variations', 'employee_id')) {
                    $table->dropForeign(['employee_id']);
                    $table->dropColumn('employee_id');
                }
            });
        }
    }
};
