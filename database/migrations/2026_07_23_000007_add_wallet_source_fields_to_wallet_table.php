<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallet')) {
            Schema::table('wallet', function (Blueprint $table) {
                if (! Schema::hasColumn('wallet', 'source_type')) {
                    $table->string('source_type', 50)->nullable()->after('stage_id');
                }
                if (! Schema::hasColumn('wallet', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }
                if (! Schema::hasColumn('wallet', 'payment_method_id')) {
                    $table->foreignId('payment_method_id')->nullable()->after('payment_mode')->constrained('payment_methods')->nullOnDelete();
                }
                if (! Schema::hasColumn('wallet', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('description')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wallet')) {
            Schema::table('wallet', function (Blueprint $table) {
                if (Schema::hasColumn('wallet', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
                if (Schema::hasColumn('wallet', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('wallet', 'source_type')) {
                    $table->dropColumn('source_type');
                }
                if (Schema::hasColumn('wallet', 'source_id')) {
                    $table->dropColumn('source_id');
                }
            });
        }
    }
};
