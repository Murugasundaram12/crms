<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Quotation extends Model
{
    use HasFactory;

    protected $table = 'quotations';
    protected $fillable = [
        'client_id',
        'project_id',
        'quotation_date',
        'amount',
        'total_amount',
        'sub_total',
        'status',
        'quotation_title',
        'main_title',
        'sub_title',
        'proposal_content',
        'client_name',
        'client_address',
        'validity_days',
        'start_date',
        'duration_days',
        'notes',
        'quotation_number',
        'discount_percent',
        'gst_percent',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(QuotationSchedule::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(QuotationTerm::class);
    }

    public function getTotalAmountAttribute($value)
    {
        $items = $this->relationLoaded('items') ? $this->items : null;

        return $items instanceof Collection && $items->isNotEmpty()
            ? $items->sum('amount')
            : $value;
    }
}
