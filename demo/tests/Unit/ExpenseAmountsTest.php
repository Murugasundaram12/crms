<?php

namespace Tests\Unit;

use App\Support\ExpenseAmounts;
use PHPUnit\Framework\TestCase;

class ExpenseAmountsTest extends TestCase
{
    public function test_partial_payment_creates_unpaid_amount(): void
    {
        $this->assertSame([
            'paid_amt' => 8000.0,
            'unpaid_amt' => 2000.0,
            'extra_amt' => 0,
        ], ExpenseAmounts::calculate(10000, 8000));
    }

    public function test_overpayment_creates_advance_amount(): void
    {
        $this->assertSame([
            'paid_amt' => 12000.0,
            'unpaid_amt' => 0,
            'extra_amt' => 2000.0,
        ], ExpenseAmounts::calculate(10000, 12000));
    }
}
