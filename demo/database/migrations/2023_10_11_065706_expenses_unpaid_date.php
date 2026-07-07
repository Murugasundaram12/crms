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
        Schema::create('expenses_unpaid_date', function (Blueprint $table) 
        {
                $table->bigIncrements('id');
              
                $table->integer('expense_id')->unsigned();
                $table->date('current_date');
                $table->integer('unpaid_amt')->default(0);
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses_unpaid_date');
    }
};
