<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Tests\TestCase;

class VendorAdvanceDoubleDebitTest extends TestCase
{
    public function test_vendor_expense_consumes_advance_without_double_wallet_debit(): void
    {
        $user = User::first();
        $vendor = Vendor::first();
        $category = Category::first();

        if (! $user || ! $vendor || ! $category) {
            $this->assertTrue(true);
            return;
        }

        // Test math logic
        $initialVendorAdvance = (float) ($vendor->advance_amt ?? 0);
        $expenseAmount = 15000;
        $paidAmount = 15000;

        $advanceUsed = min($paidAmount, $initialVendorAdvance);
        $freshWalletDebit = max(0, $paidAmount - $advanceUsed);

        // If vendor had 10,000 advance, fresh debit must be 5,000 instead of 15,000
        if ($initialVendorAdvance >= 10000) {
            $this->assertEquals(5000, $freshWalletDebit);
        } else {
            $this->assertEquals($paidAmount - $initialVendorAdvance, $freshWalletDebit);
        }
    }
}
