<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('stage_name');
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 14, 2)->nullable(); // Calculated or manual
            $table->string('status')->default('pending');
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_stages');
    }
};
