<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourSalary;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabourAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $request->filled('date') ? $request->string('date')->toString() : now()->toDateString();
        $selectedMonth = $request->filled('month') ? $request->string('month')->toString() : now()->format('Y-m');
        $selectedLabourId = $request->filled('labour_id') ? $request->integer('labour_id') : null;

        $dateCarbon = Carbon::parse($selectedDate);
        $isSunday = $dateCarbon->isSunday();

        $query = LabourAttendance::query()->with(['labour', 'employee']);

        if ($selectedLabourId) {
            $query->where('labour_id', $selectedLabourId);
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $selectedDate);
        } elseif ($request->filled('month')) {
            [$year, $month] = explode('-', $selectedMonth);
            $query->whereYear('attendance_date', $year)->whereMonth('attendance_date', $month);
        } else {
            $query->whereDate('attendance_date', $selectedDate);
        }

        $attendances = $query
            ->latest('attendance_date')
            ->paginate(15)
            ->withQueryString();

        $labours = Labour::query()->orderBy('name')->get();

        // If a specific labour is selected or month summary requested
        $summary = null;
        if ($selectedLabourId) {
            $labour = Labour::find($selectedLabourId);
            if ($labour) {
                $summary = $this->calculateMonthlySummary($labour, $selectedMonth);
            }
        }

        return view('pages.labour_attendances.index', compact(
            'attendances',
            'labours',
            'selectedDate',
            'selectedMonth',
            'selectedLabourId',
            'isSunday',
            'summary'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['present', 'absent', 'half_day'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendanceDate = Carbon::parse($validated['attendance_date']);

        if ($attendanceDate->isSunday()) {
            return redirect()->back()->withInput()->with('error', 'Sunday is a weekly off day and cannot be marked as a working attendance status.');
        }

        // Check if salary for this period is already processed
        if ($this->isSalaryPaidForDate($validated['labour_id'], $validated['attendance_date'])) {
            return redirect()->back()->withInput()->with('error', 'Attendance cannot be modified because salary for this period has already been processed.');
        }

        // Check for existing record on same date
        $existing = LabourAttendance::query()
            ->where('labour_id', $validated['labour_id'])
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Attendance record already exists for this labour on ' . $validated['attendance_date'] . '.');
        }

        LabourAttendance::create([
            'labour_id' => $validated['labour_id'],
            'employee_id' => Auth::id(),
            'attendance_date' => $validated['attendance_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Labour attendance recorded successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'attendances' => ['required', 'array'],
            'attendances.*.labour_id' => ['required', 'exists:labours,id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'absent', 'half_day', 'off'])],
            'attendances.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendanceDate = Carbon::parse($validated['attendance_date']);

        if ($attendanceDate->isSunday()) {
            return redirect()->back()->withInput()->with('error', 'Sunday is a weekly off day and cannot be marked for attendance.');
        }

        $userId = Auth::id();
        $dateStr = $attendanceDate->toDateString();
        $savedCount = 0;
        $blockedCount = 0;

        DB::transaction(function () use ($validated, $dateStr, $userId, &$savedCount, &$blockedCount) {
            foreach ($validated['attendances'] as $item) {
                if (($item['status'] ?? 'off') === 'off') {
                    continue;
                }

                if ($this->isSalaryPaidForDate($item['labour_id'], $dateStr)) {
                    $blockedCount++;
                    continue;
                }

                LabourAttendance::updateOrCreate(
                    [
                        'labour_id' => $item['labour_id'],
                        'attendance_date' => $dateStr,
                    ],
                    [
                        'employee_id' => $userId,
                        'status' => $item['status'],
                        'notes' => $item['notes'] ?? null,
                    ]
                );

                $savedCount++;
            }
        });

        $message = "Saved attendance for {$savedCount} labour(s).";
        if ($blockedCount > 0) {
            $message .= " {$blockedCount} labour(s) skipped because salary for this period was already paid.";
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(LabourAttendance $labourAttendance): RedirectResponse
    {
        if ($this->isSalaryPaidForDate($labourAttendance->labour_id, $labourAttendance->attendance_date->toDateString())) {
            return redirect()->back()->with('error', 'Cannot delete attendance for a period where salary has already been paid.');
        }

        $labourAttendance->delete();

        return redirect()->back()->with('success', 'Labour attendance deleted successfully.');
    }

    public function summaryJson(Request $request)
    {
        $labourId = $request->integer('labour_id');
        $month = $request->string('month')->toString() ?: now()->format('Y-m');

        $labour = Labour::findOrFail($labourId);
        $summary = $this->calculateMonthlySummary($labour, $month);

        return response()->json($summary);
    }

    public static function calculateMonthlySummary(Labour $labour, string $yearMonth): array
    {
        $carbonMonth = Carbon::parse($yearMonth . '-01');
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();

        // Calculate Monday-Saturday working days in month
        $workingDaysCount = 0;
        $sundayCount = 0;
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            if ($current->isSunday()) {
                $sundayCount++;
            } else {
                $workingDaysCount++;
            }
            $current->addDay();
        }

        $attendances = LabourAttendance::query()
            ->where('labour_id', $labour->id)
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();

        $payableDays = $presentDays + ($halfDays * 0.5);
        $monthlyBaseSalary = (float) ($labour->salary ?? 0);
        $dailyRate = $workingDaysCount > 0 ? ($monthlyBaseSalary / $workingDaysCount) : 0;
        $calculatedSalary = round($dailyRate * $payableDays, 2);

        return [
            'labour_id' => $labour->id,
            'labour_name' => $labour->name,
            'month' => $yearMonth,
            'period_start' => $startOfMonth->toDateString(),
            'period_end' => $endOfMonth->toDateString(),
            'total_calendar_days' => $startOfMonth->daysInMonth,
            'total_working_days' => $workingDaysCount,
            'sunday_count' => $sundayCount,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'payable_days' => $payableDays,
            'monthly_salary' => $monthlyBaseSalary,
            'daily_rate' => round($dailyRate, 2),
            'calculated_salary' => $calculatedSalary,
            'current_advance_balance' => (float) ($labour->advance_amt ?? 0),
        ];
    }

    private function isSalaryPaidForDate(int $labourId, string $date): bool
    {
        return LabourSalary::query()
            ->where('labour_id', $labourId)
            ->where('status', 'paid')
            ->where('salary_period_start', '<=', $date)
            ->where('salary_period_end', '>=', $date)
            ->exists();
    }
}
