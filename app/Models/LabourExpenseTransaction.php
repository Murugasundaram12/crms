<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourExpenseTransaction extends Model
{
    protected $table = 'labour_expense_transactions';

    protected $fillable = [
        'user_id',
        'main_category_id',
        'category_id',
        'image_path',
        'project_id',
        'description',
        'paid_amount',
        'payment_mode',
        'labour_id',
        'salary',
        'current_date',
        'current_time',
        'active_status',
        'delete_status',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'salary' => 'decimal:2',
        'current_date' => 'date',
        'active_status' => 'boolean',
        'delete_status' => 'boolean',
    ];

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class, 'main_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class, 'labour_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
