<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Labour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'job_title',
        'phone_number',
        'labour_role_id',
        'gender',
        'salary',
        'government_photo',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    public function labourRole(): BelongsTo
    {
        return $this->belongsTo(LabourRole::class);
    }
}
