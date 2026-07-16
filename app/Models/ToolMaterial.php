<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolMaterial extends Model
{
    protected $table = 'tools_materials';

    protected $fillable = [
        'item_type',
        'sku',
        'name',
        'unit',
        'image_path',
        'description',
        'date',
        'opening_quantity',
        'opening_rate',
        'opening_amount',
        'reorder_level',
        'active_status',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_quantity' => 'decimal:2',
        'opening_rate' => 'decimal:2',
        'opening_amount' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'active_status' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ToolMaterialAssignment::class);
    }

    public function getStockQuantityAttribute(): float
    {
        return array_sum(array_column($this->stockBalances(), 'quantity'));
    }

    public function getStockAmountAttribute(): float
    {
        return array_sum(array_column($this->stockBalances(), 'amount'));
    }

    public function getOfficeStockQuantityAttribute(): float
    {
        return $this->stockBalances()['office']['quantity'] ?? 0.0;
    }

    public function getSiteStockQuantityAttribute(): float
    {
        return collect($this->stockBalances())
            ->filter(fn(array $balance, string $key) => str_starts_with($key, 'site:'))
            ->sum('quantity');
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->reorder_level > 0 && $this->stock_quantity <= (float) $this->reorder_level;
    }

    public function stockBalances(): array
    {
        $balances = [
            'office' => [
                'label' => 'Office',
                'quantity' => (float) $this->opening_quantity,
                'amount' => (float) $this->opening_amount,
            ],
        ];

        foreach ($this->assignments as $assignment) {
            foreach ($assignment->locationEffects() as $effect) {
                $key = $effect['key'];

                if (! isset($balances[$key])) {
                    $balances[$key] = [
                        'label' => $effect['label'],
                        'quantity' => 0.0,
                        'amount' => 0.0,
                    ];
                }

                $balances[$key]['quantity'] += $effect['quantity'];
                $balances[$key]['amount'] += $effect['amount'];
            }
        }

        return $balances;
    }
}
