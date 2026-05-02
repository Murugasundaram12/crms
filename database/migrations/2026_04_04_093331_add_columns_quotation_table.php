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
        //
        Schema::table('quotations', function (Blueprint $table) {
            $table->integer('validity_days')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('quotations', function (Blueprint $table) {

        });
    }
};
