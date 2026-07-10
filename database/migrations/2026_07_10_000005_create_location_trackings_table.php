<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->string('type')->default('travelling');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['employee_id', 'recorded_at']);
            $table->index(['attendance_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_trackings');
    }
};
