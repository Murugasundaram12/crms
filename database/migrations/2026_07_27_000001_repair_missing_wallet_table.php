<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallet')) {
            return;
        }

        Schema::create('wallet', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('amount');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->timestamp('current_date')->nullable()->useCurrent();
            $table->text('description')->nullable();
            $table->integer('active_status')->default(1);
            $table->integer('delete_status')->default(0);
            $table->integer('payment_mode')->default(1);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->integer('stage_id')->nullable();
            $table->integer('transfer_type')->default(0);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'current_date']);
            $table->index(['client_id', 'project_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet');
    }
};
