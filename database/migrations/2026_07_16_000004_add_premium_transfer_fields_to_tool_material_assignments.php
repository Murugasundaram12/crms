<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->unique()->after('id');
            $table->string('status', 30)->default('completed')->after('reference_no');
            $table->foreignId('handled_by')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
            $table->string('receiver_name')->nullable()->after('amount');
            $table->string('vehicle_no')->nullable()->after('receiver_name');
            $table->string('purpose')->nullable()->after('vehicle_no');
        });

        DB::table('tool_material_assignments')
            ->whereNull('reference_no')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($row): void {
                DB::table('tool_material_assignments')
                    ->where('id', $row->id)
                    ->update(['reference_no' => 'TM-' . str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        Schema::table('tool_material_assignments', function (Blueprint $table) {
            $table->dropForeign(['handled_by']);
            $table->dropUnique(['reference_no']);
            $table->dropColumn([
                'reference_no',
                'status',
                'handled_by',
                'receiver_name',
                'vehicle_no',
                'purpose',
            ]);
        });
    }
};
