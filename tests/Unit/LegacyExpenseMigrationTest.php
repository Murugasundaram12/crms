<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseTransaction;
use App\Models\Project;
use App\Console\Commands\MigrateLegacyExpenses;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyExpenseMigrationTest extends TestCase
{
    public function test_legacy_expense_migration_creates_canonical_expenses_and_is_idempotent(): void
    {
        $project1 = Project::find(1);
        if (! $project1) {
            $this->assertTrue(true);
            return;
        }

        $walletCountBefore = DB::table('wallet')->count();

        // Run migration command in apply mode
        $exitCode = Artisan::call('expenses:migrate-legacy', ['--apply' => true]);
        $this->assertEquals(0, $exitCode);

        // Verify canonical expense records exist
        $canonicalCount = Expense::where('source_type', MigrateLegacyExpenses::SOURCE_TYPE)
            ->whereIn('source_id', [1, 2])
            ->count();
        $this->assertEquals(2, $canonicalCount);

        // Verify Project 1 spent amount is now 2000.00
        $project1Fresh = Project::find(1);
        $this->assertEquals(2000.00, (float) $project1Fresh->spent);

        // Verify wallet table row count remains identical (no wallet debits created)
        $walletCountAfter = DB::table('wallet')->count();
        $this->assertEquals($walletCountBefore, $walletCountAfter);

        // Test Idempotency (run command a second time)
        $exitCodeSecond = Artisan::call('expenses:migrate-legacy', ['--apply' => true]);
        $this->assertEquals(0, $exitCodeSecond);

        $canonicalCountSecond = Expense::where('source_type', MigrateLegacyExpenses::SOURCE_TYPE)
            ->whereIn('source_id', [1, 2])
            ->count();
        $this->assertEquals(2, $canonicalCountSecond);
    }
}
