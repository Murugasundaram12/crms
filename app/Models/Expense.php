<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'amount',
        'main_category_id',
        'category_id',
        'project_id',
        'user_id',
        'current_date',
        'description',
        'paid_amt',
        'unpaid_amt',
        'extra_amt',
        'image',
        'editedBy',
        'payment_mode',
        'reason',
        'labour_id',
        'vendor_id',
        'is_advance',
    ];

    protected $casts = [
        'amount' => 'integer',
        'main_category_id' => 'integer',
        'category_id' => 'integer',
        'project_id' => 'integer',
        'user_id' => 'integer',
        'current_date' => 'datetime',
        'paid_amt' => 'integer',
        'unpaid_amt' => 'integer',
        'extra_amt' => 'integer',
        'editedBy' => 'integer',
        'payment_mode' => 'integer',
        'labour_id' => 'integer',
        'vendor_id' => 'integer',
        'is_advance' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'paid_amount',
        'unpaid_amount',
        'extra_amount',
        'expense_date',
        'delete_reason',
        'delete_status',
        'payment_mode_label',
        'type',
    ];

    public static function paymentModes(): array
    {
        return [
            1 => 'Cash',
            2 => 'Bank Transfer',
            3 => 'UPI',
            4 => 'Cheque',
            5 => 'Card',
        ];
    }

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function editedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editedBy');
    }

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class, 'labour_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    protected function paidAmount(): Attribute
    {
        return Attribute::make(get: fn() => $this->paid_amt);
    }

    protected function unpaidAmount(): Attribute
    {
        return Attribute::make(get: fn() => $this->unpaid_amt);
    }

    protected function extraAmount(): Attribute
    {
        return Attribute::make(get: fn() => $this->extra_amt);
    }

    protected function expenseDate(): Attribute
    {
        return Attribute::make(get: fn() => $this->current_date);
    }

    protected function deleteReason(): Attribute
    {
        return Attribute::make(get: fn() => $this->reason);
    }

    protected function deleteStatus(): Attribute
    {
        return Attribute::make(get: fn() => $this->deleted_at !== null);
    }

    protected function paymentModeLabel(): Attribute
    {
        return Attribute::make(get: fn() => self::paymentModes()[$this->payment_mode] ?? null);
    }

    protected function type(): Attribute
    {
        return Attribute::make(get: fn() => $this->mainCategory?->name);
    }
}
