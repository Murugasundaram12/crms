<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->unique();
            }
            if (! Schema::hasColumn('payments', 'quotation_id')) {
                $table->unsignedBigInteger('quotation_id')->nullable();
            }
            if (! Schema::hasColumn('payments', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable();
            }
            if (! Schema::hasColumn('payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (! Schema::hasColumn('payments', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable();
            }
            if (! Schema::hasColumn('payments', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (Schema::hasColumn('payments', 'method')) {
                $table->string('method')->nullable()->change();
            }
        });

        if (Schema::hasTable('quotations') && Schema::hasColumn('payments', 'quotation_id')) {
            $foreignKeys = collect(Schema::getForeignKeys('payments'));
            if (! $foreignKeys->contains(fn (array $key): bool => in_array('quotation_id', $key['columns'] ?? [], true))) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->foreign('quotation_id')->references('id')->on('quotations')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('payment_stages') && Schema::hasColumn('payments', 'stage_id')) {
            $foreignKeys = collect(Schema::getForeignKeys('payments'));
            if (! $foreignKeys->contains(fn (array $key): bool => in_array('stage_id', $key['columns'] ?? [], true))) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->foreign('stage_id')->references('id')->on('payment_stages')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payments', 'payment_method_id')) {
            $foreignKeys = collect(Schema::getForeignKeys('payments'));
            if (! $foreignKeys->contains(fn (array $key): bool => in_array('payment_method_id', $key['columns'] ?? [], true))) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Preserve payment history when rolling back.
    }
};
