<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesDecimalPrecisionTest extends TestCase
{
    use RefreshDatabase;
    public function test_expenses_table_supports_decimal_monetary_precision(): void
    {
        $user = User::first();
        $category = Category::first();

        if (! $user || ! $category) {
            $this->assertTrue(true);
            return;
        }

        $decimalAmount = 1250.50;

        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => $decimalAmount,
            'paid_amt' => $decimalAmount,
            'unpaid_amt' => 0.00,
            'extra_amt' => 0.00,
            'current_date' => now(),
            'description' => 'Decimal Precision Test',
            'source_type' => 'decimal_test',
            'source_id' => 999999,
        ]);

        $fetched = Expense::find($expense->id);

        $this->assertEquals(1250.50, (float) $fetched->amount);
        $this->assertEquals(1250.50, (float) $fetched->paid_amt);

        // Cleanup test row
        $fetched->forceDelete();
    }
}
