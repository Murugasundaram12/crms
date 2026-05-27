<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'advance_amount',
        'advance_amt',
    ];

    protected $casts = [
        'advance_amount' => 'decimal:2',
        'advance_amt' => 'decimal:2',
    ];
}
