<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_code',
        'project_id',
        'employee_id',
        'title',
        'type',
        'category',
        'amount',
        'paid_amount',
        'unpaid_amount',
        'extra_amount',
        'status',
        'expense_date',
        'description',
        'active_status',
        'delete_status',
        'delete_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'unpaid_amount' => 'decimal:2',
        'extra_amount' => 'decimal:2',
        'expense_date' => 'date',
        'active_status' => 'boolean',
        'delete_status' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
