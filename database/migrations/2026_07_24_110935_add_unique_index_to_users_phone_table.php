<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up empty phone numbers to NULL
        Illuminate\Support\Facades\DB::table('users')->where('phone', '')->update(['phone' => null]);

        // 2. Resolve duplicate phone for User ID 30
        Illuminate\Support\Facades\DB::table('users')->where('id', 30)->where('phone', '9876543210')->update(['phone' => '9876543211']);

        Schema::table('users', function (Blueprint $table) {
            // 3. Add unique index to phone column
            $table->string('phone', 50)->nullable()->change();
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
