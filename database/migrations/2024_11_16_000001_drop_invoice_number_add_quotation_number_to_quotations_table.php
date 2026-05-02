<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
            $table->string('quotation_number')->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('quotation_number');
            $table->string('invoice_number')->unique()->nullable()->after('id');
        });
    }
};
