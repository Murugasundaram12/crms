<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_requests')) {
            return;
        }

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');

            $table->date('from_date');
            $table->date('to_date');

            $table->text('document')->nullable();
            $table->text('remarks')->nullable();

            $table->string('status')->default('pending'); // pending|approved|rejected

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->text('approver_remarks')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->foreign('approved_by_id')->references('id')->on('users');

            $table->index(['user_id', 'status']);
            $table->index(['leave_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
