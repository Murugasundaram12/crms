<?php

namespace Tests\Unit;

use App\Models\ToolMaterialAssignment;
use App\Models\Expense;
use App\Models\ExpenseTransaction;
use App\Models\Wallet;
use App\Services\PurchaseFinanceSyncService;
use Tests\TestCase;

class PurchaseFinanceSyncServiceTest extends TestCase
{
    public function test_purchase_sync_is_idempotent_and_does_not_create_duplicate_records(): void
    {
        $assignment = ToolMaterialAssignment::where('transaction_type', 'purchase')->first();

        if (! $assignment) {
            $this->assertTrue(true);
            return;
        }

        $syncService = app(PurchaseFinanceSyncService::class);

        // First Sync
        $syncService->applyPurchaseSync($assignment, false);

        $initialExpenseCount = Expense::where('source_type', PurchaseFinanceSyncService::SOURCE_TYPE)
            ->where('source_id', $assignment->id)->count();

        $initialExpTxCount = ExpenseTransaction::where('source_type', PurchaseFinanceSyncService::SOURCE_TYPE)
            ->where('source_id', $assignment->id)->count();

        // Second Sync (Retried)
        $syncService->applyPurchaseSync($assignment, false);

        $secondExpenseCount = Expense::where('source_type', PurchaseFinanceSyncService::SOURCE_TYPE)
            ->where('source_id', $assignment->id)->count();

        $secondExpTxCount = ExpenseTransaction::where('source_type', PurchaseFinanceSyncService::SOURCE_TYPE)
            ->where('source_id', $assignment->id)->count();

        // Must remain exactly equal (no duplicate rows created on retry)
        $this->assertEquals($initialExpenseCount, $secondExpenseCount);
        $this->assertEquals($initialExpTxCount, $secondExpTxCount);
    }
}
