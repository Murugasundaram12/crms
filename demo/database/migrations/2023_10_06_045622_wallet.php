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
        Schema::create('wallet', function (Blueprint $table) 
        {
                $table->bigIncrements('id');
                $table->integer('amount');
                $table->integer('user_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->integer('project_id')->unsigned();
                $table->date('current_date');
                $table->text('description')->nullable();
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
        //
    }
};
