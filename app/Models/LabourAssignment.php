<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'labour_id',
        'project_id',
        'employee_id',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function scopeActiveForDate($query, string $date)
    {
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public static function hasOverlappingAssignment(int $labourId, string $startDate, string $endDate, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('labour_id', $labourId)
            ->where('status', 'active')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereDate('start_date', '<=', $endDate)
                  ->whereDate('end_date', '>=', $startDate);
            })
            ->exists();
    }
}
