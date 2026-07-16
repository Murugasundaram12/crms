<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolMaterial extends Model
{
    protected $table = 'tools_materials';

    protected $fillable = [
        'name',
        'unit',
        'image_path',
        'date',
        'opening_quantity',
        'opening_rate',
        'opening_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_quantity' => 'decimal:2',
        'opening_rate' => 'decimal:2',
        'opening_amount' => 'decimal:2',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ToolMaterialAssignment::class);
    }

    public function getStockQuantityAttribute(): float
    {
        $quantity = (float) $this->opening_quantity;

        foreach ($this->assignments as $assignment) {
            $quantity += $assignment->stockEffectQuantity();
        }

        return $quantity;
    }

    public function getStockAmountAttribute(): float
    {
        $amount = (float) $this->opening_amount;

        foreach ($this->assignments as $assignment) {
            $amount += $assignment->stockEffectAmount();
        }

        return $amount;
    }
}
