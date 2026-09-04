<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourAttendance;
use App\Models\LabourSalary;
use App\Models\Project;
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
        $selectedProjectId = $request->filled('project_id') ? $request->integer('project_id') : null;

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

        $projects = Project::query()->orderBy('name')->get();
        $labours = Labour::query()->with('labourRole')->orderBy('name')->get();

        // Site-wise labour filtering: Show ONLY labours with an ACTIVE assignment for selected project & date
        $eligibleLabours = collect();
        if ($selectedProjectId) {
            $assignedLabourIds = LabourAssignment::query()
                ->where('project_id', $selectedProjectId)
                ->activeForDate($selectedDate)
                ->pluck('labour_id')
                ->toArray();

            $eligibleLabours = ! empty($assignedLabourIds)
                ? Labour::query()->with('labourRole')->whereIn('id', $assignedLabourIds)->orderBy('name')->get()
                : collect();
        }

        // Map existing attendances for eligible labours on selected date
        $existingAttendances = $eligibleLabours->isNotEmpty()
            ? LabourAttendance::query()
                ->whereDate('attendance_date', $selectedDate)
                ->whereIn('labour_id', $eligibleLabours->pluck('id'))
                ->get()
                ->keyBy('labour_id')
            : collect();

        // Check if salary is already paid for any of the eligible labours for this date
        $paidLabourIds = $eligibleLabours->isNotEmpty()
            ? LabourSalary::query()
                ->where('status', 'paid')
                ->where('salary_period_start', '<=', $selectedDate)
                ->where('salary_period_end', '>=', $selectedDate)
                ->whereIn('labour_id', $eligibleLabours->pluck('id'))
                ->pluck('labour_id')
                ->toArray()
            : [];

        // Batch-resolve projects for the paginated attendance history table
        $historyLabourIds = $attendances->pluck('labour_id')->unique()->filter()->values();
        $historyAssignments = LabourAssignment::query()
            ->with('project')
            ->whereIn('labour_id', $historyLabourIds)
            ->where('status', 'active')
            ->get();

        $summary = null;
        if ($selectedLabourId) {
            $labour = Labour::with('labourRole')->find($selectedLabourId);
            if ($labour) {
                $summary = static::calculateMonthlySummary($labour, $selectedMonth);
            }
        }

        return view('pages.labour_attendances.index', compact(
            'attendances',
            'projects',
            'labours',
            'eligibleLabours',
            'existingAttendances',
            'paidLabourIds',
            'historyAssignments',
            'selectedDate',
            'selectedMonth',
            'selectedLabourId',
            'selectedProjectId',
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
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $attendanceDate = Carbon::parse($validated['attendance_date']);
        $dateStr = $attendanceDate->toDateString();

        $projectId = ! empty($validated['project_id']) ? (int) $validated['project_id'] : null;

        if ($projectId) {
            if (! static::isLabourAssignedToProjectOnDate((int) $validated['labour_id'], $projectId, $dateStr)) {
                return redirect()->back()->withInput()->with('error', 'Attendance rejected: Selected labour is not assigned to the selected project on ' . $dateStr . '.');
            }
        } else {
            if (! static::isLabourAssignedOnDate((int) $validated['labour_id'], $dateStr)) {
                return redirect()->back()->withInput()->with('error', 'Attendance rejected: Selected labour is not assigned to an active project on ' . $dateStr . '.');
            }
        }

        if ($this->isSalaryPaidForDate((int) $validated['labour_id'], $dateStr)) {
            return redirect()->back()->withInput()->with('error', 'Attendance cannot be modified because salary for this period has already been processed.');
        }

        $existing = LabourAttendance::query()
            ->where('labour_id', $validated['labour_id'])
            ->whereDate('attendance_date', $dateStr)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Attendance record already exists for this labour on ' . $dateStr . '.');
        }

        LabourAttendance::create([
            'labour_id' => $validated['labour_id'],
            'employee_id' => Auth::id(),
            'attendance_date' => $dateStr,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Labour attendance recorded successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'attendances' => ['required', 'array'],
            'attendances.*.labour_id' => ['required', 'exists:labours,id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'absent', 'half_day', 'off'])],
            'attendances.*.notes' => ['nullable', 'string', 'max:500'],
            'attendances.*.project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $attendanceDate = Carbon::parse($validated['attendance_date']);

        $userId = Auth::id();
        $dateStr = $attendanceDate->toDateString();
        $selectedProjectId = ! empty($validated['project_id']) ? (int) $validated['project_id'] : null;

        $savedCount = 0;
        $blockedCount = 0;
        $unassignedCount = 0;
        $mismatchedCount = 0;

        DB::transaction(function () use ($validated, $dateStr, $userId, $selectedProjectId, &$savedCount, &$blockedCount, &$unassignedCount, &$mismatchedCount) {
            foreach ($validated['attendances'] as $item) {
                if (($item['status'] ?? 'off') === 'off') {
                    continue;
                }

                $labourId = (int) $item['labour_id'];
                $itemProjectId = ! empty($item['project_id']) ? (int) $item['project_id'] : $selectedProjectId;

                if ($itemProjectId) {
                    if (! static::isLabourAssignedToProjectOnDate($labourId, $itemProjectId, $dateStr)) {
                        $mismatchedCount++;
                        continue;
                    }
                } else {
                    if (! static::isLabourAssignedOnDate($labourId, $dateStr)) {
                        $unassignedCount++;
                        continue;
                    }
                }

                if ($this->isSalaryPaidForDate($labourId, $dateStr)) {
                    $blockedCount++;
                    continue;
                }

                LabourAttendance::updateOrCreate(
                    [
                        'labour_id' => $labourId,
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

        if ($savedCount === 0 && $mismatchedCount > 0) {
            return redirect()->back()->withInput()->with('error', 'Attendance rejected: Selected labour is not assigned to the selected project on ' . $dateStr . '.');
        }

        if ($savedCount === 0 && $unassignedCount > 0) {
            return redirect()->back()->withInput()->with('error', 'Attendance rejected: Selected labour is not assigned to an active project on ' . $dateStr . '.');
        }

        $message = "Saved attendance for {$savedCount} labour(s).";
        if ($mismatchedCount > 0) {
            $message .= " {$mismatchedCount} labour(s) skipped because they are not assigned to the selected project on {$dateStr}.";
        }
        if ($unassignedCount > 0) {
            $message .= " {$unassignedCount} labour(s) skipped because they have no active site assignment on {$dateStr}.";
        }
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
        $validated = $request->validate([
            'labour_id' => ['required', 'integer', 'exists:labours,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'month' => ['nullable', 'string'],
        ]);

        $labourId = (int) $validated['labour_id'];
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $month = $validated['month'] ?? now()->format('Y-m');

        $labour = Labour::with('labourRole')->findOrFail($labourId);

        if ($startDate && $endDate) {
            $summary = static::calculatePeriodSummary($labour, $startDate, $endDate);
        } else {
            $summary = static::calculateMonthlySummary($labour, $month);
        }

        return response()->json($summary);
    }

    public static function isLabourAssignedToProjectOnDate(int $labourId, int $projectId, string $dateStr): bool
    {
        return LabourAssignment::query()
            ->where('labour_id', $labourId)
            ->where('project_id', $projectId)
            ->activeForDate($dateStr)
            ->exists();
    }

    public static function isLabourAssignedOnDate(int $labourId, string $dateStr): bool
    {
        $hasAnyAssignments = LabourAssignment::query()->exists();
        if (! $hasAnyAssignments) {
            return true;
        }

        return LabourAssignment::query()
            ->activeForDate($dateStr)
            ->where('labour_id', $labourId)
            ->exists();
    }

    public static function calculatePeriodSummary(Labour $labour, string $startDateStr, string $endDateStr): array
    {
        $start = Carbon::parse($startDateStr);
        $end = Carbon::parse($endDateStr);

        $workingDaysCount = 0;
        $sundayCount = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isSunday()) {
                $sundayCount++;
            } else {
                $workingDaysCount++;
            }
            $current->addDay();
        }

        $attendances = LabourAttendance::query()
            ->where('labour_id', $labour->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $payableDays = $presentDays + ($halfDays * 0.5);

        $role = $labour->labourRole;
        $salaryType = strtolower((string) ($role?->salary_type ?? 'monthly'));
        $baseRate = (float) ($role?->salary ?? $labour->salary ?? 0);

        if ($salaryType === 'daily') {
            $dailyRate = $baseRate;
        } elseif ($salaryType === 'weekly') {
            $dailyRate = $baseRate / 6.0;
        } else {
            // Monthly: divide monthly rate by actual Mon-Sat working days in the period
            $dailyRate = $workingDaysCount > 0 ? ($baseRate / $workingDaysCount) : 0.0;
        }

        $calculatedSalary = round($dailyRate * $payableDays, 2);

        return [
            'labour_id' => $labour->id,
            'labour_name' => $labour->name,
            'salary_type' => ucfirst($salaryType),
            'base_rate' => $baseRate,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'total_calendar_days' => $start->diffInDays($end) + 1,
            'total_working_days' => $workingDaysCount,
            'sunday_count' => $sundayCount,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'payable_days' => $payableDays,
            'daily_rate' => round($dailyRate, 2),
            'calculated_salary' => $calculatedSalary,
            'current_advance_balance' => (float) ($labour->advance_amt ?? 0),
            'month' => $start->format('Y-m'),
        ];
    }

    public static function calculateMonthlySummary(Labour $labour, string $yearMonth): array
    {
        $carbonMonth = Carbon::parse($yearMonth . '-01');
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();

        $summary = static::calculatePeriodSummary($labour, $startOfMonth->toDateString(), $endOfMonth->toDateString());
        $summary['month'] = $yearMonth;

        return $summary;
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
