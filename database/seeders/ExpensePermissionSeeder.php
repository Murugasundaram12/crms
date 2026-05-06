<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExpensePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'expenses-list',
            'expenses-create',
            'expenses-edit',
            'expenses-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to admin role or specific roles
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }
    }
}
