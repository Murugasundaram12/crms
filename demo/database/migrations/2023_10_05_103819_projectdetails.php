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
        Schema::create('project_details', function (Blueprint $table) 
        {
                $table->bigIncrements('id');
                $table->string('name');
                $table->integer('advance_amt');
                $table->integer('total_amt');
                $table->integer('client_id')->unsigned();
                $table->foreign('client_id')->references('id')->on('clientdetails')->onDelete('cascade');
                $table->integer('profit')->default(0);
                $table->integer('project_status')->default(0);
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
        Schema::dropIfExists('project_details');
    }
};
