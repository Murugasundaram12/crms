<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_roles')) {
            return;
        }

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if (Schema::hasTable('employees')) {
            $mappings = DB::table('user_roles')
                ->join('employees', 'user_roles.user_id', '=', 'employees.id')
                ->join('users', 'users.email', '=', 'employees.email')
                ->select('user_roles.id', 'users.id as user_id')
                ->get();

            foreach ($mappings as $mapping) {
                DB::table('user_roles')
                    ->where('id', $mapping->id)
                    ->update(['user_id' => $mapping->user_id]);
            }
        }

        Schema::table('user_roles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_roles') || ! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }
};
