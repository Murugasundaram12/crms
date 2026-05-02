<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('user_roles')) {
            return;
        }

        $users = DB::table('users')
            ->whereNotNull('role')
            ->get(['id', 'role']);

        foreach ($users as $user) {
            $roleId = DB::table('roles')
                ->where('name', $user->role)
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $exists = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $roleId)
                ->exists();

            if (! $exists) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
