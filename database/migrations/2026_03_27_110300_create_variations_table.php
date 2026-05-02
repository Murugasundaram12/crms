<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->enum('type', ['additional', 'deduction']);
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->unsignedBigInteger('approved_by')->nullable(); // Employee ID
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variations');
    }
};
