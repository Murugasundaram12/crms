<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('activity')->nullable();
            $table->boolean('is_gps_on')->default(true);
            $table->boolean('is_mock_location')->default(false);
            $table->unsignedTinyInteger('battery_percentage')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'device_id']);
            $table->index(['last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_devices');
    }
};
