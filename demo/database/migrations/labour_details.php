<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {Schema::create('labour_details', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('job_title');
                $table->string('phone');
                $table->integer('gender');
                $table->float('salary');
                $table->string('government_image')->nullable();
                $table->integer('advance_amt')->default(0);
                $table->integer('salary_type')->default(0);
                $table->timestamps();
                $table->softDeletes();
    });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('labour_details');
    }
};
