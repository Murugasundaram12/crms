<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'Site Engineer')
            ->update(['role' => 'Employee']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Employee')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'Employee')
            ->update(['role' => 'Site Engineer']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Site Engineer')->change();
        });
    }
};
