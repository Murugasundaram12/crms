<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? trim($value) : $value,
        );
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? trim($value) : $value,
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_status', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} ({$this->name})";
    }
}
