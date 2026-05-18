<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'description',
        'current_date',
        'current_time',
        'active_status',
        'delete_status',
    ];
}
