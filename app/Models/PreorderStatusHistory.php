<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreorderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'preorder_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
    ];

    public function preorder(): BelongsTo
    {
        return $this->belongsTo(Preorder::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
