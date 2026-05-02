<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'employee_id',
        'title',
        'description',
        'type',
        'auto_repeat',
        'recurring_source_id',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'estimated_hours',
        'logged_hours',
        'is_important',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'logged_hours' => 'decimal:2',
        'is_important' => 'boolean',
        'auto_repeat' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recurringSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurring_source_id');
    }
}
