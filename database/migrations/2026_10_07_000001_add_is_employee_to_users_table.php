<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_employee')) {
                $table->boolean('is_employee')->default(false)->after('role')->index();
            }
        });

        // Backfill existing internal staff / employees
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhere('role', '!=', 'Client');
            })
            ->update(['is_employee' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_employee')) {
                $table->dropColumn('is_employee');
            }
        });
    }
};
