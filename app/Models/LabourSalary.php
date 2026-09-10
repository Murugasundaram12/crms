<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourSalary extends Model
{
    use HasFactory;

    protected $table = 'labour_salaries';

    protected $fillable = [
        'labour_id',
        'salary_period_start',
        'salary_period_end',
        'salary_amount',
        'paid_amount',
        'advance_adjusted',
        'remaining_amount',
        'payment_date',
        'payment_method_id',
        'notes',
        'paid_by',
        'status',
        'advance_paid',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'advance_adjusted' => 'decimal:2',
        'advance_paid' => 'boolean',
        'remaining_amount' => 'decimal:2',
        'salary_period_start' => 'date',
        'salary_period_end' => 'date',
        'payment_date' => 'date',
    ];

    public function labour(): BelongsTo
    {
        return $this->belongsTo(Labour::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LabourAttendance::class, 'labour_salary_id');
    }

    /**
     * Explicitly link specific attendance IDs to this salary payment within an existing transaction.
     * Validates:
     * - Existence of attendances
     * - Labour ownership (anti-tampering)
     * - Period boundaries
     * - No Sunday attendance
     * - Valid working-day status (present, half_day)
     * - Not already linked to another paid salary
     *
     * @param array<int> $attendanceIds
     * @throws \Illuminate\Validation\ValidationException
     */
    public function linkAttendances(array $attendanceIds): void
    {
        if (empty($attendanceIds) || ! \Illuminate\Support\Facades\Schema::hasColumn('labour_attendances', 'labour_salary_id')) {
            return;
        }

        $attendanceIds = array_values(array_unique(array_filter(array_map('intval', $attendanceIds))));
        if (empty($attendanceIds)) {
            return;
        }

        $attendances = LabourAttendance::query()
            ->whereIn('id', $attendanceIds)
            ->lockForUpdate()
            ->get();

        if ($attendances->count() !== count($attendanceIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'attendance_ids' => 'One or more selected attendance records could not be found.',
            ]);
        }

        $startStr = $this->salary_period_start ? \Carbon\Carbon::parse($this->salary_period_start)->toDateString() : null;
        $endStr = $this->salary_period_end ? \Carbon\Carbon::parse($this->salary_period_end)->toDateString() : null;

        foreach ($attendances as $att) {
            // 1. Verify it belongs to the same labour (anti-tampering)
            if ((int) $att->labour_id !== (int) $this->labour_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'attendance_ids' => "Attendance record #{$att->id} does not belong to labour #{$this->labour_id}.",
                ]);
            }

            // 2. Verify attendance date belongs to the salary calculation period
            $attDate = \Carbon\Carbon::parse($att->attendance_date)->toDateString();
            if ($startStr && $endStr && ($attDate < $startStr || $attDate > $endStr)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'attendance_ids' => "Attendance date {$attDate} is outside salary period {$startStr} to {$endStr}.",
                ]);
            }

            // 3. Do not allow Sunday attendance to be linked
            if (\Carbon\Carbon::parse($att->attendance_date)->isSunday()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'attendance_ids' => "Attendance ID {$att->id} on Sunday cannot be linked to salary payment.",
                ]);
            }

            // 4. Verify it is a valid working-day attendance
            if (! in_array($att->status, ['present', 'half_day'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'attendance_ids' => "Attendance ID {$att->id} does not have a valid working-day status ({$att->status}).",
                ]);
            }

            // 5. Verify it is not already linked to another paid salary
            if ($att->labour_salary_id && (int) $att->labour_salary_id !== (int) $this->id) {
                $isPaid = LabourSalary::query()
                    ->where('id', $att->labour_salary_id)
                    ->where('status', 'paid')
                    ->exists();
                if ($isPaid) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'attendance_ids' => "Attendance record #{$att->id} ({$attDate}) is already linked to paid salary #{$att->labour_salary_id}.",
                    ]);
                }
            }
        }

        foreach ($attendances as $att) {
            $att->update(['labour_salary_id' => $this->id]);
        }
    }

    protected static function booted(): void
    {
        static::deleted(function (LabourSalary $salary) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('labour_attendances', 'labour_salary_id')) {
                LabourAttendance::where('labour_salary_id', $salary->id)->update(['labour_salary_id' => null]);
            }
        });
    }
}
