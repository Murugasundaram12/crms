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
        Schema::table('quotations', function (Blueprint $table) {
            $table->date('quotation_date')->nullable()->after('client_id');
            $table->decimal('amount', 14, 2)->nullable()->after('quotation_date');
            $table->string('quotation_title')->nullable()->after('amount');
            $table->string('main_title')->nullable()->after('quotation_title');
            $table->string('sub_title')->nullable()->after('main_title');
            $table->longText('proposal_content')->nullable()->after('sub_title');
            $table->string('client_name')->nullable()->after('proposal_content');
            $table->text('client_address')->nullable()->after('client_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'quotation_date',
                'amount',
                'quotation_title',
                'main_title',
                'sub_title',
                'proposal_content',
                'client_name',
                'client_address'
            ]);
        });
    }
};
