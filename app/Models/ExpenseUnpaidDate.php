<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseUnpaidDate extends Model
{
    protected $table = 'expenses_unpaid_date';

    protected $fillable = [
        'expense_id',
        'vendor_expense_transaction_id',
        'user_id',
        'paid_amount',
        'current_date',
        'current_time',
        'notes',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'current_date' => 'date',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
