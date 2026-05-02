<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('phone_number');
            $table->foreignId('labour_role_id')->constrained('labour_roles')->cascadeOnDelete();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->decimal('salary', 12, 2);
            $table->string('government_photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labours');
    }
};
