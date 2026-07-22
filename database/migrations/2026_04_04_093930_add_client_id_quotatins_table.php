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
        if (! Schema::hasTable('quotations') || Schema::hasColumn('quotations', 'client_id')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'client_id')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
