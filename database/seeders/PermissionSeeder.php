<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    private const MODULES = [
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
        'vendors' => 'Vendors',
        'main-categories' => 'Main Categories',
        'categories' => 'Categories',
        'employees-salary' => 'Employee Salary',
    ];

    private const ACTIONS = [
        'list' => ['label' => 'List', 'legacy' => 'view'],
        'create' => ['label' => 'Create', 'legacy' => 'create'],
        'edit' => ['label' => 'Edit', 'legacy' => 'edit'],
        'delete' => ['label' => 'Delete', 'legacy' => 'delete'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MODULES as $module => $moduleLabel) {
            foreach (self::ACTIONS as $action => $meta) {
                $this->upsertPermission(
                    module: $module,
                    moduleLabel: $moduleLabel,
                    action: $action,
                    actionLabel: $meta['label'],
                    legacyAction: $meta['legacy'],
                );
            }
        }
    }

    private function upsertPermission(
        string $module,
        string $moduleLabel,
        string $action,
        string $actionLabel,
        string $legacyAction
    ): void {
        $key = "{$module}-{$action}";
        $name = "{$actionLabel} {$moduleLabel}";
        $legacyKey = "{$module}.{$legacyAction}";

        $permission = Permission::query()->where('key', $key)->first();
        $legacyPermission = Permission::query()->where('key', $legacyKey)->first();

        if ($legacyPermission && ! $permission) {
            $legacyPermission->update([
                'name' => $name,
                'key' => $key,
            ]);

            return;
        }

        if ($legacyPermission && $permission && $legacyPermission->id !== $permission->id) {
            $this->mergePermissionRelations($legacyPermission->id, $permission->id);
            $legacyPermission->delete();
        }

        Permission::query()->updateOrCreate(
            ['key' => $key],
            ['name' => $name]
        );
    }

    private function mergePermissionRelations(int $sourcePermissionId, int $targetPermissionId): void
    {
        $roleAssignments = DB::table('role_permission')
            ->where('permission_id', $sourcePermissionId)
            ->pluck('role_id');

        foreach ($roleAssignments as $roleId) {
            DB::table('role_permission')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $targetPermissionId,
                ],
                []
            );
        }

        if (DB::getSchemaBuilder()->hasTable('model_has_permissions')) {
            $modelAssignments = DB::table('model_has_permissions')
                ->where('permission_id', $sourcePermissionId)
                ->get(['model_id', 'model_type']);

            foreach ($modelAssignments as $assignment) {
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

        DB::table('role_permission')
            ->where('permission_id', $sourcePermissionId)
            ->delete();

        if (DB::getSchemaBuilder()->hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('permission_id', $sourcePermissionId)
                ->delete();
        }
    }
}
