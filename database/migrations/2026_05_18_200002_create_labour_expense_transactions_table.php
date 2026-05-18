<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_expense_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('category_id');

            $table->string('image_path')->nullable();

            $table->unsignedBigInteger('project_id')->nullable();

            $table->text('description')->nullable();

            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('payment_mode');

            $table->unsignedBigInteger('labour_id');
            $table->decimal('salary', 18, 2)->default(0);

            $table->date('current_date');
            $table->string('current_time', 20);

            $table->boolean('active_status')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('main_category_id')->references('id')->on('main_categories')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('labour_id')->references('id')->on('labours')->restrictOnDelete();

            // Use an explicit shorter index name to avoid MySQL identifier length issues
            $table->index(['main_category_id', 'category_id', 'project_id', 'labour_id'], 'lab_exp_tx_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_expense_transactions');
    }
};
