<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('labour_salaries')) {
            Schema::create('labour_salaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id')->constrained('labours')->cascadeOnDelete();
                $table->date('salary_period_start')->nullable();
                $table->date('salary_period_end')->nullable();
                $table->decimal('salary_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2)->default(0);
                $table->date('payment_date');
                $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('paid');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_salaries');
    }
};
