<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

use App\Models\PaymentStage;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'payment_code',
        'project_id',
        'client_id',
        'quotation_id',
        'stage_id',
        'transaction_id',
        'payment_method',
        'amount',
        'due_date',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PaymentStage::class, 'stage_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function getMethodAttribute($value): ?string
    {
        return $value ?? $this->attributes['payment_method'] ?? null;
    }

    public function setMethodAttribute(?string $value): void
    {
        if (Schema::hasColumn($this->getTable(), 'payment_method')) {
            $this->attributes['payment_method'] = $value;
            return;
        }

        $this->attributes['method'] = $value;
    }
}
