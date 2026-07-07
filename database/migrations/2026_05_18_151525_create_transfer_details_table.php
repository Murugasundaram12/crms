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
        if (Schema::hasTable('transferdetails')) {
            return;
        }

        Schema::create('transferdetails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('transfer_type'); // employee | vendor
            $table->integer('amount')->default(0);
            $table->string('payment_mode');
            $table->text('description')->nullable();
            $table->date('current_date');
            $table->string('current_time');
            $table->boolean('active_status')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->timestamps();

            $table->index(['transfer_type', 'employee_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferdetails');
    }
};
