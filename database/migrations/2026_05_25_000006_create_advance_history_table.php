<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('advance_history')) {
            return;
        }

        Schema::create('advance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id')->nullable()->constrained('labours')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('entry_type')->comment('credit,settle,withdraw');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('current_date')->nullable();
            $table->string('current_time', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_history');
    }
};
