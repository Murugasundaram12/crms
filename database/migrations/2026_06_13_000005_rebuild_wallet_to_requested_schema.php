<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wallet');

        Schema::create('wallet', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('amount');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('project_id');
            $table->timestamp('current_date')->useCurrent()->useCurrentOnUpdate();
            $table->text('description')->nullable();
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->integer('payment_mode');
            $table->timestamps();
            $table->integer('stage_id')->nullable();
            $table->integer('transfer_type')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet');
    }
};
