<?php

namespace App\Support;

final class ExpenseAmounts
{
    public static function calculate(float $amount, float $paid): array
    {
        return [
            'paid_amt' => $paid,
            'unpaid_amt' => max($amount - $paid, 0),
            'extra_amt' => max($paid - $amount, 0),
        ];
    }
}
