<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmployeePayrollService
{
    public function calculateMonthlySalary(
        User $employee,
        string $period,
        ?float $customMonthlySalary = null,
        float $otherDeductions = 0.0,
        float $overtimeAmount = 0.0,
        int $halfDayThresholdMinutes = 240
    ): array {
        [$startDate, $endDate] = $this->parsePeriodDates($period);
        $monthlySalary = $customMonthlySalary ?? (float) ($employee->salary_amount ?? $employee->salary ?? 0.0);

        $attendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', '>=', $startDate->toDateString())
            ->whereDate('attendance_date', '<=', $endDate->toDateString())
            ->get()
            ->keyBy(fn(Attendance $att) => $att->attendance_date->toDateString());

        $approvedLeaves = LeaveRequest::query()
            ->with('leaveType')
            ->where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $endDate->toDateString())
            ->whereDate('to_date', '>=', $startDate->toDateString())
            ->get();

        $workingDays = 0;
        $weekoffDays = 0;
        $holidayDays = 0;
        $presentDays = 0;
        $halfDays = 0;
        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;
        $absentDays = 0;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();

            if ($currentDate->dayOfWeek === Carbon::SUNDAY) {
                $weekoffDays++;
                $currentDate->addDay();
                continue;
            }

            $workingDays++;

            $leaveOnDate = $approvedLeaves->first(function (LeaveRequest $leave) use ($currentDate) {
                return $currentDate->gte($leave->from_date) && $currentDate->lte($leave->to_date);
            });

            if ($leaveOnDate) {
                $leaveTypeName = Str::lower($leaveOnDate->leaveType?->name ?? '');
                if (Str::contains($leaveTypeName, ['unpaid', 'lwp', 'loss of pay', 'without pay'])) {
                    $unpaidLeaveDays++;
                } else {
                    $paidLeaveDays++;
                }
                $currentDate->addDay();
                continue;
            }

            $attendanceOnDate = $attendances->get($dateStr);

            if ($attendanceOnDate) {
                $status = Str::lower($attendanceOnDate->status ?? '');
                $workedMinutes = $attendanceOnDate->worked_minutes;

                $isHalfDay = ($status === 'half_day' || $status === 'halfday') ||
                    ($status === 'present' && $workedMinutes !== null && $workedMinutes > 0 && $workedMinutes < $halfDayThresholdMinutes);

                if ($isHalfDay) {
                    $halfDays++;
                } else {
                    $presentDays++;
                }
            } else {
                $absentDays++;
            }

            $currentDate->addDay();
        }

        $perDaySalary = $workingDays > 0 ? ($monthlySalary / $workingDays) : 0.0;
        $halfDayDeduction = $halfDays * ($perDaySalary / 2.0);
        $unpaidLeaveDeduction = $unpaidLeaveDays * $perDaySalary;
        $absentDeduction = $absentDays * $perDaySalary;
        $attendanceDeduction = $halfDayDeduction + $unpaidLeaveDeduction + $absentDeduction;
        $grossSalary = $monthlySalary;
        $netSalary = max(0.0, $grossSalary - $attendanceDeduction - $otherDeductions + $overtimeAmount);

        return [
            'user_id' => $employee->id,
            'user_name' => $employee->name,
            'salary_period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_days_in_month' => $startDate->daysInMonth,
            'working_days' => $workingDays,
            'weekoff_days' => $weekoffDays,
            'holiday_days' => $holidayDays,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'absent_days' => $absentDays,
            'monthly_salary' => round($monthlySalary, 2),
            'per_day_salary' => round($perDaySalary, 2),
            'gross_salary' => round($grossSalary, 2),
            'half_day_deduction' => round($halfDayDeduction, 2),
            'unpaid_leave_deduction' => round($unpaidLeaveDeduction, 2),
            'absent_deduction' => round($absentDeduction, 2),
            'attendance_deduction' => round($attendanceDeduction, 2),
            'other_deductions' => round($otherDeductions, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'net_salary' => round($netSalary, 2),
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    private function parsePeriodDates(string $period): array
    {
        $periodTrimmed = trim($period);

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $periodTrimmed)) {
                $start = Carbon::createFromFormat('Y-m-d', $periodTrimmed . '-01')->startOfDay();
            } else {
                $start = Carbon::parse($periodTrimmed)->startOfMonth();
            }
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth()->startOfDay();

        return [$start, $end];
    }
}
