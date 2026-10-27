<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabourAttendance extends Model
{
    protected $fillable = ['labour_id', 'employee_id', 'attendance_date', 'status', 'notes', 'labour_salary_id'];
    protected $casts = ['attendance_date' => 'date'];
    public function setAttendanceDateAttribute($value): void
    {
        $this->attributes['attendance_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : null;
    }

    public function labour(): BelongsTo { return $this->belongsTo(Labour::class); }
    public function employee(): BelongsTo { return $this->belongsTo(User::class, 'employee_id'); }
    public function labourSalary(): BelongsTo { return $this->belongsTo(LabourSalary::class); }
}
