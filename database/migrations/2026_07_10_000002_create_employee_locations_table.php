<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('address')->nullable();
            $table->timestamp('tracked_at');
            $table->timestamps();

            $table->index(['user_id', 'tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_locations');
    }
};
