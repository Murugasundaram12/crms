<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('project_code')->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('type');
            $table->string('priority')->default('medium');
            $table->string('status')->default('planning');
            $table->integer('progress')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->decimal('spent', 14, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
