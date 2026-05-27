<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceHistory extends Model
{
    protected $table = 'advance_history';

    protected $fillable = [
        'labour_id',
        'vendor_id',
        'labour_expense_transaction_id',
        'amount',
        'entry_type',
        'notes',
        'user_id',
        'current_date',
        'current_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_date' => 'date',
    ];

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }
}
