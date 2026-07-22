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
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'quotation_date')) {
                $table->date('quotation_date')->nullable()->after('client_id');
            }
            if (! Schema::hasColumn('quotations', 'amount')) {
                $table->decimal('amount', 14, 2)->nullable()->after('quotation_date');
            }
            if (! Schema::hasColumn('quotations', 'quotation_title')) {
                $table->string('quotation_title')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('quotations', 'main_title')) {
                $table->string('main_title')->nullable()->after('quotation_title');
            }
            if (! Schema::hasColumn('quotations', 'sub_title')) {
                $table->string('sub_title')->nullable()->after('main_title');
            }
            if (! Schema::hasColumn('quotations', 'proposal_content')) {
                $table->longText('proposal_content')->nullable()->after('sub_title');
            }
            if (! Schema::hasColumn('quotations', 'client_name')) {
                $table->string('client_name')->nullable()->after('proposal_content');
            }
            if (! Schema::hasColumn('quotations', 'client_address')) {
                $table->text('client_address')->nullable()->after('client_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'quotation_date',
                'amount',
                'quotation_title',
                'main_title',
                'sub_title',
                'proposal_content',
                'client_name',
                'client_address',
            ]);
        });
    }
};
