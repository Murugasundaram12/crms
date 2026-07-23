<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorExpenseTransaction extends Model
{
    protected $table = 'vendor_expense_transactions';

    protected $fillable = [
        'user_id',
        'main_category_id',
        'category_id',
        'image_path',
        'project_id',
        'description',
        'amount',
        'paid_amount',
        'unpaid_amount',
        'extra_amount',
        'payment_method_id',
        'vendor_id',
        'salary',
        'current_date',
        'current_time',
        'active_status',
        'delete_status',
        'delete_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'unpaid_amount' => 'decimal:2',
        'extra_amount' => 'decimal:2',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
