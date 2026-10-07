<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AttendanceLeaveIntegrationService
{
    /**
     * Normalize an input date (string, DateTimeInterface, or Carbon) to a Y-m-d string.
     */
    public function normalizeDate(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }

        return Carbon::parse((string) $date)->toDateString();
    }

    /**
     * Retrieve any approved LeaveRequest record covering a given date for an employee.
     */
    public function getApprovedLeaveForDate(int $userId, mixed $date): ?LeaveRequest
    {
        $targetDate = $this->normalizeDate($date);

        return LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $targetDate)
            ->whereDate('to_date', '>=', $targetDate)
            ->first();
    }

    /**
     * Check if an employee is on approved leave on a given date.
     */
    public function isOnApprovedLeave(int $userId, mixed $date): bool
    {
        return $this->getApprovedLeaveForDate($userId, $date) !== null;
    }

    /**
     * Find existing attendance records for an employee within a given date range.
     */
    public function getExistingAttendanceConflictsForUser(int $userId, mixed $fromDate, mixed $toDate): Collection
    {
        $startDate = $this->normalizeDate($fromDate);
        $endDate = $this->normalizeDate($toDate);

        return Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->orderBy('attendance_date')
            ->get();
    }

    /**
     * Find existing attendance records that conflict with the dates of a LeaveRequest.
     */
    public function getExistingAttendanceConflictsForLeave(LeaveRequest $leaveRequest): Collection
    {
        return $this->getExistingAttendanceConflictsForUser(
            (int) $leaveRequest->user_id,
            $leaveRequest->from_date,
            $leaveRequest->to_date
        );
    }

    /**
     * Determine whether a leave request can be safely approved without conflicting with existing attendance.
     */
    public function canApproveLeave(LeaveRequest $leaveRequest): bool
    {
        return $this->getExistingAttendanceConflictsForLeave($leaveRequest)->isEmpty();
    }
}
