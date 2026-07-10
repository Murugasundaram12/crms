<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_name',
        'project_id',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? $this->attributes['stage_name'] ?? null;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'stage_id');
    }
}
