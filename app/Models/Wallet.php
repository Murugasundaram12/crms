<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $table = 'wallet';

    protected $fillable = [
        'user_id',
        'client_id',
        'project_id',
        'amount',
        'payment_method_id',
        'transfer_type',
        'stage_id',
        'description',
        'current_date',
        'active_status',
        'delete_status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'current_date' => 'datetime',
        'active_status' => 'integer',
        'delete_status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PaymentStage::class, 'stage_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
