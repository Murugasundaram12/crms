<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Preorder extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_PARTIALLY_DELIVERED = 'partially_delivered';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_HOLD = 'hold';

    protected $fillable = [
        'reference_no',
        'tool_material_id',
        'vendor_id',
        'quantity',
        'unit',
        'rate',
        'expected_rate',
        'estimated_amount',
        'gst_percent',
        'gst_amount',
        'total_amount',
        'advance_amount',
        'remaining_amount',
        'expected_delivery_date',
        'required_date',
        'purchase_date',
        'preorder_date',
        'payment_method_id',
        'status',
        'delivery_status',
        'payment_status',
        'notes',
        'created_by',
        'updated_by',
        'approved_by',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'expected_rate' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'required_date' => 'date',
        'purchase_date' => 'date',
        'preorder_date' => 'date',
        'approval_date' => 'datetime',
    ];

    public function toolMaterial(): BelongsTo
    {
        return $this->belongsTo(ToolMaterial::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ToolMaterialAssignment::class, 'preorder_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PreorderStatusHistory::class)->latest();
    }

    public function advances(): HasMany
    {
        return $this->hasMany(PreorderAdvance::class)->latest('payment_date');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PreorderDelivery::class)->latest('delivery_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PreorderDocument::class)->latest();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PreorderAuditLog::class)->latest();
    }

    public function totalDeliveredQuantity(): float
    {
        return (float) $this->deliveries()->sum('quantity');
    }

    public function remainingQuantity(): float
    {
        return max(0.0, (float) $this->quantity - $this->totalDeliveredQuantity());
    }

    public function totalAdvancePaid(): float
    {
        return (float) $this->advances()->sum('amount');
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_ORDERED,
            self::STATUS_PARTIALLY_DELIVERED,
            self::STATUS_DELIVERED,
            self::STATUS_CLOSED,
        ], true);
    }

    public function canBeConvertedToPurchase(): bool
    {
        return $this->isApproved() && $this->status !== self::STATUS_CLOSED && $this->remainingQuantity() > 0;
    }

    public function recalculateStatuses(): void
    {
        $deliveredQty = $this->totalDeliveredQuantity();
        $orderedQty = (float) $this->quantity;
        $totalAdvance = $this->totalAdvancePaid();
        $totalAmount = (float) $this->total_amount;

        // Delivery Status
        if ($deliveredQty >= $orderedQty && $orderedQty > 0) {
            $this->delivery_status = 'delivered';
        } elseif ($deliveredQty > 0) {
            $this->delivery_status = 'partially_delivered';
        } else {
            $this->delivery_status = 'pending';
        }

        // Payment Status
        if ($totalAdvance >= $totalAmount && $totalAmount > 0) {
            $this->payment_status = 'paid';
        } elseif ($totalAdvance > 0) {
            $this->payment_status = 'partially_paid';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->advance_amount = $totalAdvance;
        $this->remaining_amount = max(0, $totalAmount - $totalAdvance);

        $this->save();
    }
}
