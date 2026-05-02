<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permission')) {
            return;
        }

        $permissionMap = [
            'employees-salary-list' => 'List Employee Salary',
            'employees-salary-create' => 'Create Employee Salary',
            'employees-salary-edit' => 'Edit Employee Salary',
            'employees-salary-delete' => 'Delete Employee Salary',
        ];

        foreach ($permissionMap as $key => $name) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['name' => $name]
            );
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['Super Admin', 'Manager'])
            ->pluck('id');

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_keys($permissionMap))
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permission')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', [
                'employees-salary-list',
                'employees-salary-create',
                'employees-salary-edit',
                'employees-salary-delete',
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
