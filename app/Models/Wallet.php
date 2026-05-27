<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $table = 'wallet';

    protected $fillable = [
        'user_id',
        'amount',
        'transfer_type',
        'description',
        'reference_type',
        'reference_id',
        'current_date',
        'current_time',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

