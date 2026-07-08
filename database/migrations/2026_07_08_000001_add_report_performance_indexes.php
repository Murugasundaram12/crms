<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('expenses', 'expenses_report_date_project_deleted_idx', ['current_date', 'project_id', 'deleted_at']);
        $this->addIndexIfMissing('employee_salaries', 'employee_salaries_report_created_idx', ['created_at']);
        $this->addIndexIfMissing('labours', 'labours_report_created_deleted_idx', ['created_at', 'deleted_at']);
        $this->addIndexIfMissing('payments', 'payments_report_status_idx', ['status']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('expenses', 'expenses_report_date_project_deleted_idx');
        $this->dropIndexIfExists('employee_salaries', 'employee_salaries_report_created_idx');
        $this->dropIndexIfExists('labours', 'labours_report_created_deleted_idx');
        $this->dropIndexIfExists('payments', 'payments_report_status_idx');
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
                return false;
            }

            return collect(Schema::getIndexes($table))
                ->contains(fn(array $existingIndex): bool => ($existingIndex['name'] ?? null) === $index);
        }

        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
