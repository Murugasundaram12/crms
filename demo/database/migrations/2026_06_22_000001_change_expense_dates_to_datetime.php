<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('expenses', 'current_date')) {
                DB::statement('ALTER TABLE `expenses` MODIFY `current_date` DATETIME NOT NULL');
            }
            if (Schema::hasColumn('expenses_unpaid_date', 'current_date')) {
                DB::statement('ALTER TABLE `expenses_unpaid_date` MODIFY `current_date` DATETIME NOT NULL');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('expenses', 'current_date')) {
                DB::statement('ALTER TABLE `expenses` MODIFY `current_date` DATE NOT NULL');
            }
            if (Schema::hasColumn('expenses_unpaid_date', 'current_date')) {
                DB::statement('ALTER TABLE `expenses_unpaid_date` MODIFY `current_date` DATE NOT NULL');
            }
        }
    }
};
