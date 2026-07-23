<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferDetails extends Model
{
    protected $table = 'transferdetails';

    protected $fillable = [
        'user_id',
        'employee_id',
        'vendor_id',
        'transfer_type',
        'amount',
        'payment_mode',
        'payment_method_id',
        'description',
        'current_date',
        'current_time',
        'active_status',
        'delete_status',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
