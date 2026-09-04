<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('labour_assignments')) {
            Schema::create('labour_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id')->constrained('labours')->cascadeOnDelete();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['labour_id', 'status', 'start_date', 'end_date']);
                $table->index(['project_id', 'status', 'start_date', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_assignments');
    }
};
