<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('wallet') && Schema::hasColumn('wallet', 'amount')) {
            Schema::table('wallet', function (Blueprint $table) {
                $table->decimal('amount', 14, 2)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallet') && Schema::hasColumn('wallet', 'amount')) {
            // Guarded rollback: refuse to downgrade if fractional decimal amounts exist
            $hasFractional = DB::table('wallet')
                ->whereRaw('amount != ROUND(amount, 0)')
                ->exists();

            if ($hasFractional) {
                throw new \RuntimeException(
                    'Cannot rollback wallet.amount from DECIMAL(14,2) to INTEGER: ' .
                    'fractional decimal amounts exist in the wallet table. ' .
                    'Rolling back would cause silent monetary truncation.'
                );
            }

            Schema::table('wallet', function (Blueprint $table) {
                $table->integer('amount')->change();
            });
        }
    }
};
