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
        Schema::create('clientdetails', function (Blueprint $table) 
    {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone');
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientdetails');
    }
};
