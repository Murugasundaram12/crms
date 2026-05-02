<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabourRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'salary_type',
        'salary',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    public function labours(): HasMany
    {
        return $this->hasMany(Labour::class);
    }
}
