<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'quotation_number')) {
                $table->string('quotation_number')->nullable()->unique();
            }
            if (! Schema::hasColumn('quotations', 'quotation_date')) {
                $table->date('quotation_date')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'amount')) {
                $table->decimal('amount', 14, 2)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'quotation_title')) {
                $table->string('quotation_title')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'main_title')) {
                $table->string('main_title')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'sub_title')) {
                $table->string('sub_title')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'proposal_content')) {
                $table->longText('proposal_content')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'client_name')) {
                $table->string('client_name')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'client_address')) {
                $table->text('client_address')->nullable();
            }
        });
    }

    public function down(): void
    {
        // These columns are required by the current quotation workflow.
    }
};
