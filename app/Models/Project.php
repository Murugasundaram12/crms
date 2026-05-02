<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Quotation;
use App\Models\PaymentStage;
use App\Models\Variation;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_code',
        'client_id',
        'manager_id',
        'name',
        'description',
        'type',
        'priority',
        'status',
        'progress',
        'start_date',
        'end_date',
        'budget',
        'spent',
        'location',
    ];

    protected $casts = [
        'progress' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function paymentStages(): HasMany
    {
        return $this->hasMany(PaymentStage::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function getFinalBillAttribute(): float
    {
        $quotation = $this->quotations()->latest('created_at')->first();
        $quotationTotal = $quotation?->total_amount ?? 0.0;

        $variationsNet = $this->variations()
            ->where('status', 'approved')
            ->get()
            ->sum(function ($variation) {
                return $variation->type === 'additional' ? $variation->amount : -$variation->amount;
            });

        $paymentsSum = $this->payments()
            ->where('status', 'paid')
            ->sum('amount');

        return (float) ($quotationTotal + $variationsNet - $paymentsSum);
    }
}
