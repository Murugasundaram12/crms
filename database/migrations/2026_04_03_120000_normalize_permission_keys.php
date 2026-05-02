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
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permission')) {
            return;
        }

        $modules = [
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'clients' => 'Clients',
            'projects' => 'Projects',
            'employees' => 'Employees',
            'tasks' => 'Tasks',
            'payments' => 'Payments',
            'payment-stages' => 'Payment Stages',
            'variations' => 'Variations',
            'labour-roles' => 'Labour Roles',
            'labours' => 'Labours',
            'quotations' => 'Quotations',
        ];

        $actions = [
            'list' => ['label' => 'List', 'legacy' => 'view'],
            'create' => ['label' => 'Create', 'legacy' => 'create'],
            'edit' => ['label' => 'Edit', 'legacy' => 'edit'],
            'delete' => ['label' => 'Delete', 'legacy' => 'delete'],
        ];

        foreach ($modules as $module => $moduleLabel) {
            foreach ($actions as $action => $meta) {
                $this->normalizePermission(
                    module: $module,
                    moduleLabel: $moduleLabel,
                    action: $action,
                    actionLabel: $meta['label'],
                    legacyAction: $meta['legacy'],
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function normalizePermission(
        string $module,
        string $moduleLabel,
        string $action,
        string $actionLabel,
        string $legacyAction
    ): void {
        $now = now();
        $key = "{$module}-{$action}";
        $name = "{$actionLabel} {$moduleLabel}";
        $legacyKey = "{$module}.{$legacyAction}";

        $permission = DB::table('permissions')->where('key', $key)->first();
        $legacyPermission = DB::table('permissions')->where('key', $legacyKey)->first();

        if ($legacyPermission && ! $permission) {
            DB::table('permissions')
                ->where('id', $legacyPermission->id)
                ->update([
                    'name' => $name,
                    'key' => $key,
                    'updated_at' => $now,
                ]);

            return;
        }

        if ($legacyPermission && $permission && $legacyPermission->id !== $permission->id) {
            $this->moveRoleAssignments($legacyPermission->id, $permission->id, $now);
            $this->moveModelAssignments($legacyPermission->id, $permission->id);

            DB::table('role_permission')->where('permission_id', $legacyPermission->id)->delete();

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->where('permission_id', $legacyPermission->id)->delete();
            }

            DB::table('permissions')->where('id', $legacyPermission->id)->delete();
        }

        if ($permission) {
            DB::table('permissions')
                ->where('id', $permission->id)
                ->update([
                    'name' => $name,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('permissions')->insert([
            'name' => $name,
            'key' => $key,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function moveRoleAssignments(int $sourcePermissionId, int $targetPermissionId, mixed $now): void
    {
        $roleIds = DB::table('role_permission')
            ->where('permission_id', $sourcePermissionId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permission')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $targetPermissionId,
                ],
                [
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function moveModelAssignments(int $sourcePermissionId, int $targetPermissionId): void
    {
        if (! Schema::hasTable('model_has_permissions')) {
            return;
        }

        $assignments = DB::table('model_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['model_id', 'model_type']);

        foreach ($assignments as $assignment) {
            DB::table('model_has_permissions')->updateOrInsert(
                [
                    'permission_id' => $targetPermissionId,
                    'model_id' => $assignment->model_id,
                    'model_type' => $assignment->model_type,
                ],
                []
            );
        }
    }
};
