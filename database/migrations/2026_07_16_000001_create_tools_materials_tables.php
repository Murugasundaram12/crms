<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('tool_material_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_material_id')->constrained('tools_materials')->cascadeOnDelete();
            $table->foreignId('from_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('to_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->enum('transfer_type', ['site_to_office', 'site_to_site']);
            $table->dateTime('transferred_at');
            $table->timestamps();
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_material_assignments');
        Schema::dropIfExists('tools_materials');

        if (Schema::hasTable('permissions') && Schema::hasTable('role_permission')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('key', array_keys($this->permissionMap()))
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }
    }

    private function addPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permission')) {
            return;
        }

        foreach ($this->permissionMap() as $key => $name) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['name' => $name]
            );
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Manager'])
            ->pluck('id');

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_keys($this->permissionMap()))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    []
                );
            }
        }
    }

    private function permissionMap(): array
    {
        return [
            'tools-materials-list' => 'List Tools & Materials',
            'tools-materials-create' => 'Create Tools & Materials',
            'tools-materials-edit' => 'Edit Tools & Materials',
            'tools-materials-delete' => 'Delete Tools & Materials',
        ];
    }
};
