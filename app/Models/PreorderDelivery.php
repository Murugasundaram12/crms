<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreorderDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'preorder_id',
        'delivery_number',
        'quantity',
        'delivery_date',
        'challan_no',
        'received_by',
        'notes',
        'assignment_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    public function preorder(): BelongsTo
    {
        return $this->belongsTo(Preorder::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ToolMaterialAssignment::class, 'assignment_id');
    }
}
