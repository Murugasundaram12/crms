<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->boolean('active_status')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('units')->insert([
            ['name' => 'Square Feet', 'code' => 'sft', 'description' => 'Area measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cubic Feet', 'code' => 'cft', 'description' => 'Volume measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Numbers', 'code' => 'Nos', 'description' => 'Count measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pieces', 'code' => 'pcs', 'description' => 'Piece count', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Square Meter', 'code' => 'Sqm', 'description' => 'Area measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cubic Meter', 'code' => 'Cum', 'description' => 'Volume measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Meter', 'code' => 'm', 'description' => 'Length measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Feet', 'code' => 'ft', 'description' => 'Length measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kilogram', 'code' => 'kg', 'description' => 'Weight measurement', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bag', 'code' => 'bag', 'description' => 'Bag count', 'active_status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $existingUnits = collect();
        if (Schema::hasTable('tools_materials') && Schema::hasColumn('tools_materials', 'unit')) {
            $existingUnits = $existingUnits->merge(DB::table('tools_materials')->whereNotNull('unit')->pluck('unit'));
        }
        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'unit')) {
            $existingUnits = $existingUnits->merge(DB::table('quotation_items')->whereNotNull('unit')->pluck('unit'));
        }

        $existingUnits
            ->map(fn($unit) => trim((string) $unit))
            ->filter()
            ->unique()
            ->each(function (string $unit) use ($now): void {
                if (DB::table('units')->where('code', $unit)->exists()) {
                    return;
                }

                DB::table('units')->insert([
                    'name' => $unit,
                    'code' => $unit,
                    'description' => 'Imported from existing records',
                    'active_status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        foreach (['list' => 'List', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete'] as $action => $label) {
            DB::table('permissions')->updateOrInsert(
                ['key' => "units-{$action}"],
                ['name' => "{$label} Units", 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $unitPermissionIds = DB::table('permissions')
            ->whereIn('key', ['units-list', 'units-create', 'units-edit', 'units-delete'])
            ->pluck('id');

        DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Manager'])
            ->pluck('id')
            ->each(function ($roleId) use ($unitPermissionIds): void {
                foreach ($unitPermissionIds as $permissionId) {
                    DB::table('role_permission')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('key', ['units-list', 'units-create', 'units-edit', 'units-delete'])
            ->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::dropIfExists('units');
    }
};
