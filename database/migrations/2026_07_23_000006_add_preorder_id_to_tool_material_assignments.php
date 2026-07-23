<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tool_material_assignments')) {
            Schema::table('tool_material_assignments', function (Blueprint $table) {
                if (! Schema::hasColumn('tool_material_assignments', 'preorder_id')) {
                    $table->foreignId('preorder_id')->nullable()->after('id')->constrained('preorders')->nullOnDelete();
                }
                if (! Schema::hasColumn('tool_material_assignments', 'payment_method_id')) {
                    $table->foreignId('payment_method_id')->nullable()->after('handled_by')->constrained('payment_methods')->nullOnDelete();
                }
                if (! Schema::hasColumn('tool_material_assignments', 'advance_amount')) {
                    $table->decimal('advance_amount', 12, 2)->default(0)->after('amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tool_material_assignments')) {
            Schema::table('tool_material_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('tool_material_assignments', 'preorder_id')) {
                    $table->dropForeign(['preorder_id']);
                    $table->dropColumn('preorder_id');
                }
                if (Schema::hasColumn('tool_material_assignments', 'payment_method_id')) {
                    $table->dropForeign(['payment_method_id']);
                    $table->dropColumn('payment_method_id');
                }
                if (Schema::hasColumn('tool_material_assignments', 'advance_amount')) {
                    $table->dropColumn('advance_amount');
                }
            });
        }
    }
};
