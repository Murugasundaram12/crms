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
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('amount', 14, 2)->change();
                $table->decimal('paid_amt', 14, 2)->default(0.00)->change();
                $table->decimal('unpaid_amt', 14, 2)->default(0.00)->change();
                $table->decimal('extra_amt', 14, 2)->nullable()->default(0.00)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->integer('amount')->change();
                $table->integer('paid_amt')->default(0)->change();
                $table->integer('unpaid_amt')->default(0)->change();
                $table->integer('extra_amt')->nullable()->default(0)->change();
            });
        }
    }
};
