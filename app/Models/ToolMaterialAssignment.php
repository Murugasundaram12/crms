<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMaterialAssignment extends Model
{
    protected $fillable = [
        'tool_material_id',
        'from_project_id',
        'to_project_id',
        'vendor_id',
        'transfer_type',
        'transaction_type',
        'source_type',
        'destination_type',
        'quantity',
        'unit',
        'rate',
        'amount',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function toolMaterial(): BelongsTo
    {
        return $this->belongsTo(ToolMaterial::class);
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function stockEffectQuantity(): float
    {
        return match ($this->transaction_type) {
            'purchase' => (float) $this->quantity,
            'return_to_vendor', 'damage_wastage' => -1 * (float) $this->quantity,
            default => 0.0,
        };
    }

    public function stockEffectAmount(): float
    {
        return match ($this->transaction_type) {
            'purchase' => (float) $this->amount,
            'return_to_vendor', 'damage_wastage' => -1 * (float) $this->amount,
            default => 0.0,
        };
    }

    public function transactionLabel(): string
    {
        return match ($this->transaction_type) {
            'purchase' => 'Purchase',
            'issue_to_site' => 'Issue to Site',
            'return_to_office' => 'Return to Office',
            'site_to_site' => 'Site to Site',
            'return_to_vendor' => 'Return to Vendor',
            'damage_wastage' => 'Damage / Wastage',
            default => ucwords(str_replace('_', ' ', (string) $this->transaction_type)),
        };
    }
}
