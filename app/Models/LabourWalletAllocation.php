<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourWalletAllocation extends Model
{
    use HasFactory;

    protected $table = 'labour_wallet_allocations';

    protected $fillable = [
        'reversal_transaction_id',
        'credit_transaction_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reversalTransaction(): BelongsTo
    {
        return $this->belongsTo(LabourWalletTransaction::class, 'reversal_transaction_id');
    }

    public function creditTransaction(): BelongsTo
    {
        return $this->belongsTo(LabourWalletTransaction::class, 'credit_transaction_id');
    }
}
