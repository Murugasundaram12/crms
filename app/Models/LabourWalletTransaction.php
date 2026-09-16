<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabourWalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'labour_wallet_transactions';

    protected $fillable = [
        'labour_id',
        'employee_id',
        'type',
        'amount',
        'payment_method_id',
        'notes',
        'created_by',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocationsAsReversal(): HasMany
    {
        return $this->hasMany(LabourWalletAllocation::class, 'reversal_transaction_id');
    }

    public function allocationsAsCredit(): HasMany
    {
        return $this->hasMany(LabourWalletAllocation::class, 'credit_transaction_id');
    }
}
