<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendanceQuery = Attendance::query()->with('user')->latest('attendance_date')->latest('check_in_at');

        if ($request->filled('user_id')) {
            $attendanceQuery->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from_date')) {
            $attendanceQuery->whereDate('attendance_date', '>=', $request->date('from_date')->toDateString());
        }

        if ($request->filled('to_date')) {
            $attendanceQuery->whereDate('attendance_date', '<=', $request->date('to_date')->toDateString());
        }

        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'checked_out') {
                $attendanceQuery->whereNotNull('check_out_at');
            }

            if ($request->string('status')->toString() === 'checked_in') {
                $attendanceQuery->whereNull('check_out_at');
            }
        }

        $attendances = $attendanceQuery->paginate(15)->withQueryString();
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('pages.attendance.index', compact('attendances', 'users'));
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $openAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->whereNull('check_out_at')
            ->first();

        if ($openAttendance) {
            return redirect()->route('dashboard')->with('error', 'You have already checked in today.');
        }

        Attendance::query()->create([
            'user_id' => $user->id,
            'attendance_date' => $today,
            'check_in_at' => now(),
            'status' => 'present',
        ]);

        return redirect()->route('dashboard')->with('success', 'Checked in successfully.');
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $openAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();

        if (! $openAttendance) {
            return redirect()->route('dashboard')->with('error', 'No active check-in found for today.');
        }

        $checkoutTime = now();
        $workedMinutes = $openAttendance->check_in_at->diffInMinutes($checkoutTime);

        $openAttendance->update([
            'check_out_at' => $checkoutTime,
            'worked_minutes' => $workedMinutes,
        ]);

        return redirect()->route('dashboard')->with('success', 'Checked out successfully.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        DB::transaction(function () use ($attendance): void {
            LocationTracking::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            $attendance->delete();
        });

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance deleted successfully.');
    }
}
