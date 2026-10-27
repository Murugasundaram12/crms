<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labour extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'job_title',
        'phone',
        'phone_number',
        'labour_role',
        'labour_role_id',
        'gender',
        'salary',
        'advance_amt',
        'government_image',
        'government_photo',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'advance_amt' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Labour $labour): void {
            if ($labour->isDirty('labour_role_id') && ! $labour->isDirty('salary') && $labour->labour_role_id) {
                $role = LabourRole::find($labour->labour_role_id);
                if ($role && (float) $role->salary > 0) {
                    $labour->salary = $role->salary;
                }
            }
        });
    }

    public function labourRole(): BelongsTo
    {
        return $this->belongsTo(LabourRole::class);
    }

    public function assignments()
    {
        return $this->hasMany(LabourAssignment::class);
    }
}
