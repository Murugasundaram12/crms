<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseTransaction;
use App\Models\MainCategory;
use App\Models\ToolMaterialAssignment;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseFinanceSyncService
{
    public const SOURCE_TYPE = 'tool_material_purchase';

    public function __construct(
        private CrmBalanceService $balanceService
    ) {}

    public function replacePurchaseSync(?ToolMaterialAssignment $oldAssignment, ToolMaterialAssignment $newAssignment, bool $debitWallet): void
    {
        if ($oldAssignment) {
            $this->reversePurchaseSync($oldAssignment);
        }

        $this->applyPurchaseSync($newAssignment, $debitWallet);
    }

    public function applyPurchaseSync(ToolMaterialAssignment $assignment, bool $debitWallet): void
    {
        if (! $this->shouldSync($assignment)) {
            return;
        }

        $amount = round((float) $assignment->amount, 2);
        $paidAmount = round((float) $assignment->advance_amount, 2);
        $unpaidAmount = max(0, $amount - $paidAmount);
        $projectId = $assignment->destination_type === 'site' ? $assignment->to_project_id : null;
        $userId = (int) ($assignment->handled_by ?: auth()->id() ?: 1);
        $description = $this->description($assignment);

        if ($debitWallet && $paidAmount > 0) {
            $existingWallet = Wallet::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', (int) $assignment->id)
                ->first();

            if (! $existingWallet) {
                $this->balanceService->recordWalletTransaction(
                    $userId,
                    $paidAmount,
                    'debit',
                    self::SOURCE_TYPE,
                    (int) $assignment->id,
                    $assignment->payment_method_id ? (int) $assignment->payment_method_id : null,
                    $description,
                    $userId,
                    $projectId ? (int) $projectId : null
                );
            }
        }

        [$mainCategoryId, $categoryId] = $this->expenseCategoryIds();

        if (Schema::hasTable('expenses')) {
            Expense::query()->updateOrCreate(
                [
                    'source_type' => Schema::hasColumn('expenses', 'source_type') ? self::SOURCE_TYPE : null,
                    'source_id' => Schema::hasColumn('expenses', 'source_id') ? (int) $assignment->id : null,
                ],
                array_filter([
                    'amount' => (int) round($amount),
                    'main_category_id' => $mainCategoryId,
                    'category_id' => $categoryId,
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'current_date' => $assignment->transferred_at ?? now(),
                    'description' => $description,
                    'paid_amt' => (int) round($paidAmount),
                    'unpaid_amt' => (int) round($unpaidAmount),
                    'extra_amt' => 0,
                    'payment_method_id' => $assignment->payment_method_id,
                    'vendor_id' => $assignment->vendor_id,
                ], fn($value) => $value !== null)
            );
        }

        if (Schema::hasTable('expense_transactions')) {
            ExpenseTransaction::query()->updateOrCreate(
                [
                    'source_type' => Schema::hasColumn('expense_transactions', 'source_type') ? self::SOURCE_TYPE : null,
                    'source_id' => Schema::hasColumn('expense_transactions', 'source_id') ? (int) $assignment->id : null,
                ],
                array_filter([
                    'user_id' => $userId,
                    'main_category_id' => $mainCategoryId,
                    'category_id' => $categoryId,
                    'project_id' => $projectId,
                    'description' => $description,
                    'paid_amount' => $paidAmount,
                    'payment_mode' => (string) ($assignment->payment_method_id ?: 1),
                    'payment_method_id' => $assignment->payment_method_id,
                    'current_date' => optional($assignment->transferred_at)->toDateString() ?? now()->toDateString(),
                    'current_time' => optional($assignment->transferred_at)->format('h:i:s A') ?? now()->format('h:i:s A'),
                    'active_status' => true,
                    'delete_status' => false,
                ], fn($value) => $value !== null)
            );
        }
    }

    public function reversePurchaseSync(ToolMaterialAssignment $assignment): void
    {
        if (! $this->shouldSync($assignment)) {
            return;
        }

        $paidAmount = round((float) $assignment->advance_amount, 2);
        $userId = (int) ($assignment->handled_by ?: auth()->id() ?: 1);

        if ($paidAmount > 0) {
            $walletQuery = Wallet::query();
            if (Schema::hasColumn('wallet', 'source_type') && Schema::hasColumn('wallet', 'source_id')) {
                $walletQuery->where('source_type', self::SOURCE_TYPE)->where('source_id', $assignment->id);
            } else {
                $walletQuery->where('description', $this->description($assignment));
            }

            if ($walletQuery->exists()) {
                $this->balanceService->creditUserWallet($userId, $paidAmount, 'Reversal of ' . self::SOURCE_TYPE, self::SOURCE_TYPE, (int) $assignment->id);
                $walletQuery->delete();
            }
        }

        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'source_type') && Schema::hasColumn('expenses', 'source_id')) {
            Expense::withTrashed()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', $assignment->id)
                ->forceDelete();
        }

        if (Schema::hasTable('expense_transactions') && Schema::hasColumn('expense_transactions', 'source_type') && Schema::hasColumn('expense_transactions', 'source_id')) {
            ExpenseTransaction::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', $assignment->id)
                ->delete();
        }
    }

    private function shouldSync(ToolMaterialAssignment $assignment): bool
    {
        return ToolMaterialAssignment::isStockEffectiveStatus($assignment->status)
            && $assignment->transaction_type === 'purchase'
            && (float) $assignment->amount > 0;
    }

    private function expenseCategoryIds(): array
    {
        $mainCategory = MainCategory::query()->firstOrCreate(
            ['name' => 'TOOLS & MATERIALS'],
            ['status' => 'active']
        );

        $category = Category::query()->firstOrCreate(
            ['name' => 'PURCHASE'],
            ['main_category_id' => $mainCategory->id]
        );

        if (Schema::hasTable('category_main_category')) {
            DB::table('category_main_category')->updateOrInsert(
                ['category_id' => $category->id, 'main_category_id' => $mainCategory->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        return [(int) $mainCategory->id, (int) $category->id];
    }

    private function description(ToolMaterialAssignment $assignment): string
    {
        $itemName = $assignment->toolMaterial?->name ?? 'Tool / Material';

        return 'Purchase ' . ($assignment->reference_no ?? ('#' . $assignment->id)) . ' - ' . $itemName;
    }
}
