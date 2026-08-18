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
        if (! Schema::hasTable('transferdetails')) {
            return;
        }

        Schema::table('transferdetails', function (Blueprint $table) {
            if (! Schema::hasColumn('transferdetails', 'labour_id')) {
                $table->unsignedBigInteger('labour_id')->nullable()->after('vendor_id');
                $table->foreign('labour_id')->references('id')->on('labours')->nullOnDelete();
                $table->index(['transfer_type', 'employee_id', 'vendor_id', 'labour_id'], 'idx_transferdetails_types_recipients');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('transferdetails')) {
            return;
        }

        Schema::table('transferdetails', function (Blueprint $table) {
            if (Schema::hasColumn('transferdetails', 'labour_id')) {
                $table->dropForeign(['labour_id']);
                $table->dropIndex('idx_transferdetails_types_recipients');
                $table->dropColumn('labour_id');
            }
        });
    }
};
