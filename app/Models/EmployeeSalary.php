<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'salary',
        'salary_type',
        'user_id',
        'salary_period',
        'salary_amount',
        'paid_amount',
        'remaining_amount',
        'payment_date',
        'payment_method_id',
        'notes',
        'status',
        'paid_by',
        'monthly_salary',
        'working_days',
        'present_days',
        'half_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'absent_days',
        'per_day_salary',
        'gross_salary',
        'half_day_deduction',
        'unpaid_leave_deduction',
        'absent_deduction',
        'attendance_deduction',
        'other_deductions',
        'overtime_amount',
        'net_salary',
        'calculated_at',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'salary_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'payment_date' => 'date',
        'monthly_salary' => 'decimal:2',
        'working_days' => 'integer',
        'present_days' => 'decimal:2',
        'half_days' => 'integer',
        'paid_leave_days' => 'integer',
        'unpaid_leave_days' => 'integer',
        'absent_days' => 'integer',
        'per_day_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'half_day_deduction' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'attendance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
