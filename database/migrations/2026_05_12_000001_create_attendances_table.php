<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
