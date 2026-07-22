<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools_materials', function (Blueprint $table) {
            $table->string('unit', 50)->default('Nos')->after('name');
            $table->decimal('opening_quantity', 12, 2)->default(0)->after('date');
            $table->decimal('opening_rate', 12, 2)->default(0)->after('opening_quantity');
            $table->decimal('opening_amount', 12, 2)->default(0)->after('opening_rate');
        });

        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->dropForeign(['from_project_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tool_material_assignments MODIFY from_project_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE tool_material_assignments MODIFY transfer_type VARCHAR(50) NOT NULL');
        }

        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->foreign('from_project_id')->references('id')->on('projects')->nullOnDelete();
            $table->string('transaction_type', 50)->default('site_to_office')->after('transfer_type');
            $table->string('source_type', 50)->nullable()->after('transaction_type');
            $table->string('destination_type', 50)->nullable()->after('source_type');
            $table->foreignId('vendor_id')->nullable()->after('to_project_id')->constrained('vendors')->nullOnDelete();
            $table->decimal('quantity', 12, 2)->default(0)->after('vendor_id');
            $table->string('unit', 50)->default('Nos')->after('quantity');
            $table->decimal('rate', 12, 2)->default(0)->after('unit');
            $table->decimal('amount', 12, 2)->default(0)->after('rate');
            $table->text('notes')->nullable()->after('amount');
        });

        DB::table('tool_material_assignments')->update([
            'transaction_type' => DB::raw('transfer_type'),
            'source_type' => 'site',
            'destination_type' => DB::raw("CASE WHEN transfer_type = 'site_to_site' THEN 'site' ELSE 'office' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->dropForeign(['from_project_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropColumn([
                'transaction_type',
                'source_type',
                'destination_type',
                'vendor_id',
                'quantity',
                'unit',
                'rate',
                'amount',
                'notes',
            ]);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tool_material_assignments MODIFY transfer_type ENUM('site_to_office', 'site_to_site') NOT NULL");
            DB::statement('ALTER TABLE tool_material_assignments MODIFY from_project_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->foreign('from_project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::table('tools_materials', function (Blueprint $table) {
            $table->dropColumn([
                'unit',
                'opening_quantity',
                'opening_rate',
                'opening_amount',
            ]);
        });
    }
};
