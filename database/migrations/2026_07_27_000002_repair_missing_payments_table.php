<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable()->unique();
            $table->string('payment_code')->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('payment_stages')->nullOnDelete();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('method')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status')->default('pending');
            $table->date('payment_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_date']);
            $table->index(['client_id', 'project_id', 'quotation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
