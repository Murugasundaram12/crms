<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'method') && ! Schema::hasColumn('payments', 'payment_method')) {
                $table->renameColumn('method', 'payment_method');
            }
            if (Schema::hasColumn('payments', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank_transfer'])->default('bank_transfer')->change();
            }
            if (! Schema::hasColumn('payments', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'stage_id')) {
                $table->foreignId('stage_id')->nullable()->constrained('payment_stages')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
            $table->dropConstrainedForeignId('stage_id');
            $table->string('payment_method')->change();
            $table->renameColumn('payment_method', 'method');
        });
    }
};
