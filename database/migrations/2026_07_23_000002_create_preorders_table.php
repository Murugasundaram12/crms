<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('preorders')) {
            Schema::create('preorders', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique();
                $table->foreignId('tool_material_id')->constrained('tools_materials')->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->decimal('quantity', 12, 2)->default(0);
                $table->string('unit', 50)->default('Nos');
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('advance_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2)->default(0);
                $table->date('expected_delivery_date')->nullable();
                $table->date('preorder_date');
                $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
                $table->enum('status', ['preorder', 'purchase', 'delivered', 'hold'])->default('preorder');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('preorders');
    }
};
