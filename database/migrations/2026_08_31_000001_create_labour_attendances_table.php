<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('labour_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id')->constrained('labours')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default('present'); // present|absent|leave
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['labour_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('labour_attendances'); }
};
