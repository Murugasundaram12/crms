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
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->unique('user_id');
            }
        });

        // Deterministic backfill: safely match employees to users strictly by unique email
        if (Schema::hasTable('employees') && Schema::hasTable('users')) {
            $employees = DB::table('employees')->whereNull('user_id')->get();

            foreach ($employees as $employee) {
                if (blank($employee->email)) {
                    continue;
                }

                $matchingUserId = DB::table('users')
                    ->where('email', $employee->email)
                    ->value('id');

                if ($matchingUserId) {
                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update(['user_id' => $matchingUserId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
