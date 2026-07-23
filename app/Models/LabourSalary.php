<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourSalary extends Model
{
    use HasFactory;

    protected $table = 'labour_salaries';

    protected $fillable = [
        'labour_id',
        'salary_period_start',
        'salary_period_end',
        'salary_amount',
        'paid_amount',
        'remaining_amount',
        'payment_date',
        'payment_method_id',
        'notes',
        'paid_by',
        'status',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'salary_period_start' => 'date',
        'salary_period_end' => 'date',
        'payment_date' => 'date',
    ];

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
