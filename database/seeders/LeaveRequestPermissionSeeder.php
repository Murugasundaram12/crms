<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class LeaveRequestPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionMap = [
            'leave-requests-list' => 'List Leave Requests',
            'leave-requests-create' => 'Create Leave Requests',
            'leave-requests-edit' => 'Edit Leave Requests',
            'leave-requests-delete' => 'Delete Leave Requests',
        ];

        foreach ($permissionMap as $key => $name) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name]
            );
        }

        $superAdminRole = Role::query()->where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $permissionIds = Permission::query()
                ->whereIn('key', array_keys($permissionMap))
                ->pluck('id')
                ->all();

            $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
