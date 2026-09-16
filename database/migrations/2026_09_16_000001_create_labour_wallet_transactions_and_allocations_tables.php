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
        if (! Schema::hasTable('labour_wallet_transactions')) {
            Schema::create('labour_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id')->constrained('labours')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 20)->comment('credit, reverse');
                $table->decimal('amount', 14, 2);
                $table->foreignId('payment_method_id')->constrained('payment_methods');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('current_date')->nullable();
                $table->string('current_time', 20)->nullable();
                $table->timestamps();

                $table->index(['labour_id', 'employee_id']);
                $table->index(['labour_id', 'type']);
            });
        }

        if (! Schema::hasTable('labour_wallet_allocations')) {
            Schema::create('labour_wallet_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reversal_transaction_id')
                    ->constrained('labour_wallet_transactions')
                    ->cascadeOnDelete();
                $table->foreignId('credit_transaction_id')
                    ->constrained('labour_wallet_transactions')
                    ->cascadeOnDelete();
                $table->decimal('amount', 14, 2);
                $table->timestamps();

                $table->index(['reversal_transaction_id'], 'idx_lwa_reversal');
                $table->index(['credit_transaction_id'], 'idx_lwa_credit');
                $table->index(['reversal_transaction_id', 'credit_transaction_id'], 'idx_lwa_reversal_credit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labour_wallet_allocations');
        Schema::dropIfExists('labour_wallet_transactions');
    }
};
