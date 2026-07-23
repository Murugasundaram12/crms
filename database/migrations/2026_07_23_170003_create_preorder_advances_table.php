<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorder_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_id')->constrained('preorders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('paid_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('wallet_debited')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preorder_advances');
    }
};
