<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    private const ROLE_PERMISSIONS = [
        'Super Admin' => '*',
        'Manager' => [
            'permissions-list',
            'permissions-create',
            'permissions-edit',
            'permissions-delete',
            'clients-list',
            'clients-create',
            'clients-edit',
            'clients-delete',
            'projects-list',
            'projects-create',
            'projects-edit',
            'projects-delete',
            'leave-requests-list',
            'leave-requests-edit',
            'leave-requests-delete',
            'employees-list',

            'employees-create',
            'employees-edit',
            'employees-delete',
            'employees-salary-list',
            'employees-salary-create',
            'employees-salary-edit',
            'employees-salary-delete',
            'tasks-list',
            'tasks-create',
            'tasks-edit',
            'tasks-delete',
            'variations-list',
            'variations-create',
            'variations-edit',
            'variations-delete',
            'quotations-list',
            'quotations-create',
            'quotations-edit',
            'quotations-delete',
            'expenses-list',
            'expenses-create',
            'expenses-edit',
            'expenses-delete',
            'transfers-list',
            'transfers-create',
            'transfers-edit',
            'transfers-delete',
        ],
        'Employee' => [
            'projects-list',
        ],
    ];

    public function run(): void
    {
        $allPermissions = \App\Models\Permission::query()->get()->keyBy('key');

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionKeys) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName],
                ['description' => $this->defaultDescription($roleName)]
            );

            if ($permissionKeys === '*') {
                $role->permissions()->sync($allPermissions->pluck('id'));
                continue;
            }

            $permissionIds = collect($permissionKeys)
                ->map(fn(string $key) => $allPermissions->get($key)?->id)
                ->filter()
                ->values();

            $role->permissions()->sync($permissionIds);
        }

        $this->syncUsersFromRoleColumn();
        $this->ensureSuperAdminUserExists();
    }

    private function syncUsersFromRoleColumn(): void
    {
        User::query()
            ->whereNotNull('role')
            ->get()
            ->each(function (User $user): void {
                $role = Role::query()->where('name', $user->role)->first();

                if (! $role) {
                    return;
                }

                $user->roles()->syncWithoutDetaching([$role->id]);
            });
    }

    private function ensureSuperAdminUserExists(): void
    {
        $adminRole = Role::query()->where('name', 'Super Admin')->first();

        if (! $adminRole) {
            return;
        }

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'phone' => '1234567890',
                'designation' => 'Administrator',
                'role' => 'Super Admin',
                'status' => 'active',
                'password' => bcrypt('password'),
            ]
        );

        if ($adminUser->role !== 'Super Admin') {
            $adminUser->forceFill(['role' => 'Super Admin'])->save();
        }

        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
    }

    private function defaultDescription(string $roleName): string
    {
        return match ($roleName) {
            'Super Admin' => 'Full system access',
            'Manager' => 'Operational access across core modules',
            'Employee' => 'Basic project access',
            default => $roleName,
        };
    }
}
