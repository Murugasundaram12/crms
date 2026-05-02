<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'main_title',
        'main_title_order',
        'item_order',
        'description',
        'nos',
        'length',
        'breadth',
        'depth',
        'quantity',
        'unit',
        'price',
        'rate',
        'amount',
    ];

    protected $casts = [
        'nos' => 'decimal:2',
        'length' => 'decimal:2',
        'breadth' => 'decimal:2',
        'depth' => 'decimal:2',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
