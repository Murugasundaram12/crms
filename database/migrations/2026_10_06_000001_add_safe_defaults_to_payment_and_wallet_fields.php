<?php

 

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('payment_stages') && \Illuminate\Support\Facades\Schema::hasColumn('payment_stages', 'percentage')) {
            \Illuminate\Support\Facades\Schema::table('payment_stages', function (\Illuminate\Database\Schema\Blueprint $table): void {
                $table->unsignedTinyInteger('percentage')->default(0)->change();
            });
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('wallet') && \Illuminate\Support\Facades\Schema::hasColumn('wallet', 'payment_mode')) {
            \Illuminate\Support\Facades\Schema::table('wallet', function (\Illuminate\Database\Schema\Blueprint $table): void {
                $table->integer('payment_mode')->default(1)->change();
            });
        }
    }

    public function down(): void
    {
        // Keep existing production data intact when rolling back; the defaults
        // are compatibility safeguards for legacy inserts.
    }
};
