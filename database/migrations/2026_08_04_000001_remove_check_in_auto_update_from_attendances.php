<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances') || ! Schema::hasColumn('attendances', 'check_in_at')) {
            return;
        }

        DB::statement('ALTER TABLE attendances MODIFY check_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        DB::statement(<<<'SQL'
            UPDATE attendances
            SET check_in_at = DATE_SUB(check_out_at, INTERVAL worked_minutes MINUTE)
            WHERE check_out_at IS NOT NULL
              AND worked_minutes IS NOT NULL
              AND ABS(TIMESTAMPDIFF(MINUTE, check_in_at, check_out_at) - CAST(worked_minutes AS SIGNED)) > 1
        SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances') || ! Schema::hasColumn('attendances', 'check_in_at')) {
            return;
        }

        DB::statement('ALTER TABLE attendances MODIFY check_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
