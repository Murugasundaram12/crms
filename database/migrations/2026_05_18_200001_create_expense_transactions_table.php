<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_transactions', function (Blueprint $table) {
            // If this migration is re-run during development, do not hard-fail.
            // (Keeps local testing moving forward.)
            // Note: Laravel migrations normally prevent re-run; this is just defensive.
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('category_id');

            $table->string('image_path')->nullable();

            $table->unsignedBigInteger('project_id')->nullable();

            $table->text('description')->nullable();

            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('payment_mode');

            // Stored as Y-m-d
            $table->date('current_date');
            // Stored as time string, e.g. 01:14:20 AM
            $table->string('current_time', 20);

            $table->boolean('active_status')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('main_category_id')->references('id')->on('main_categories')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();

            // Use an explicit shorter index name to avoid MySQL identifier length issues
            $table->index(['main_category_id', 'category_id', 'project_id'], 'exp_tx_mc_c_prj_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_transactions');
    }
};
