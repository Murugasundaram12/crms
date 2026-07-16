<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMaterialAssignment extends Model
{
    protected $fillable = [
        'reference_no',
        'status',
        'tool_material_id',
        'from_project_id',
        'to_project_id',
        'vendor_id',
        'handled_by',
        'transfer_type',
        'transaction_type',
        'source_type',
        'destination_type',
        'quantity',
        'unit',
        'rate',
        'amount',
        'receiver_name',
        'vehicle_no',
        'purpose',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function stockEffectQuantity(): float
    {
        if ($this->status !== 'completed') {
            return 0.0;
        }

        return match ($this->transaction_type) {
            'purchase' => (float) $this->quantity,
            'return_to_vendor', 'damage_wastage' => -1 * (float) $this->quantity,
            default => 0.0,
        };
    }

    public function locationEffects(): array
    {
        if ($this->status !== 'completed') {
            return [];
        }

        $quantity = (float) $this->quantity;
        $amount = (float) $this->amount;

        return match ($this->transaction_type) {
            'purchase' => [
                $this->positiveEffect($this->destination_type === 'site' ? 'site' : 'office', $quantity, $amount, $this->toProject),
            ],
            'issue_to_site' => [
                $this->negativeEffect('office', $quantity, $amount),
                $this->positiveEffect('site', $quantity, $amount, $this->toProject),
            ],
            'return_to_office' => [
                $this->negativeEffect('site', $quantity, $amount, $this->fromProject),
                $this->positiveEffect('office', $quantity, $amount),
            ],
            'site_to_site' => [
                $this->negativeEffect('site', $quantity, $amount, $this->fromProject),
                $this->positiveEffect('site', $quantity, $amount, $this->toProject),
            ],
            'return_to_vendor', 'damage_wastage' => [
                $this->negativeEffect($this->source_type === 'site' ? 'site' : 'office', $quantity, $amount, $this->fromProject),
            ],
            default => [],
        };
    }

    private function positiveEffect(string $type, float $quantity, float $amount, ?Project $project = null): array
    {
        return $this->locationEffect($type, $quantity, $amount, $project);
    }

    private function negativeEffect(string $type, float $quantity, float $amount, ?Project $project = null): array
    {
        return $this->locationEffect($type, -1 * $quantity, -1 * $amount, $project);
    }

    private function locationEffect(string $type, float $quantity, float $amount, ?Project $project = null): array
    {
        if ($type === 'site') {
            $projectId = $project?->id ?? 0;

            return [
                'key' => 'site:' . $projectId,
                'label' => $project?->name ?? 'Site',
                'quantity' => $quantity,
                'amount' => $amount,
            ];
        }

        return [
            'key' => 'office',
            'label' => 'Office',
            'quantity' => $quantity,
            'amount' => $amount,
        ];
    }

    public function stockEffectAmount(): float
    {
        if ($this->status !== 'completed') {
            return 0.0;
        }

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

    public function statusLabel(): string
    {
        return ucfirst((string) $this->status);
    }
}
