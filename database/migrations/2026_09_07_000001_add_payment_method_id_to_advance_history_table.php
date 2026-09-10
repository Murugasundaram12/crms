<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('advance_history') && ! Schema::hasColumn('advance_history', 'payment_method_id')) {
            Schema::table('advance_history', function (Blueprint $table) {
                $table->foreignId('payment_method_id')->nullable()->after('user_id')->constrained('payment_methods')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('advance_history') && Schema::hasColumn('advance_history', 'payment_method_id')) {
            Schema::table('advance_history', function (Blueprint $table) {
                $table->dropForeign(['payment_method_id']);
                $table->dropColumn('payment_method_id');
            });
        }
    }
};
