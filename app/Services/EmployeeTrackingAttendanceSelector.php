<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeeTrackingAttendanceSelector
{
    public function forTimeline(User $employee, string $date, Carbon $timelineStart, Carbon $timelineEnd): Collection
    {
        $datedAttendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();

        if ($datedAttendances->isNotEmpty()) {
            return $datedAttendances;
        }

        $start = $timelineStart->toDateTimeString();
        $end = $timelineEnd->toDateTimeString();

        return Attendance::query()
            ->where('user_id', $employee->id)
            ->where(function ($query) use ($date, $start, $end): void {
                $query->whereDate('attendance_date', $date)
                    ->orWhereBetween('check_in_at', [$start, $end])
                    ->orWhereBetween('check_out_at', [$start, $end])
                    ->orWhereExists(function ($subQuery) use ($start, $end): void {
                        $subQuery->selectRaw('1')
                            ->from('location_trackings')
                            ->whereColumn('location_trackings.attendance_id', 'attendances.id')
                            ->whereRaw('COALESCE(location_trackings.recorded_at, location_trackings.created_at) BETWEEN ? AND ?', [$start, $end]);
                    });
            })
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();
    }
}
