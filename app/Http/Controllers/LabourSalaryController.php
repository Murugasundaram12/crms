<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourSalary;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LabourSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $query = LabourSalary::query()->with(['labour', 'paymentMethod', 'payer']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('labour', function ($labourQuery) use ($search) {
                        $labourQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date('date_to')->toDateString());
        }

        $labourSalaries = $query
            ->latest('payment_date')
            ->latest()
            ->paginate((int) $request->input('paginate', 10))
            ->withQueryString();

        return view('pages.labour_salaries.index', compact('labourSalaries'));
    }

    public function create(Request $request): View
    {
        $labours = Labour::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $payerWalletBalance = (float) (Auth::user()->wallet ?? 0);
        $selectedLabourId = $request->integer('labour_id') ?: null;

        return view('pages.labour_salaries.create', compact('labours', 'paymentMethods', 'payerWalletBalance', 'selectedLabourId'));
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'salary_period_start' => ['required', 'date'],
            'salary_period_end' => ['required', 'date', 'after_or_equal:salary_period_start'],
        ]);

        $labour = Labour::findOrFail((int) $validated['labour_id']);
        $startDate = Carbon::parse($validated['salary_period_start']);
        $endDate = Carbon::parse($validated['salary_period_end']);

        $summary = LabourAttendanceController::calculatePeriodSummary($labour, $validated['salary_period_start'], $validated['salary_period_end']);

        return response()->json($summary);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLabourSalary($request);

        $payer = Auth::user();
        $paidAmount = round((float) $validated['paid_amount'], 2);
        $advanceAdjusted = round((float) ($validated['advance_adjusted'] ?? 0), 2);
        $salaryAmount = round((float) $validated['salary_amount'], 2);
        $advancePaid = (bool) ($validated['advance_paid'] ?? false);
        $attendanceIds = $validated['attendance_ids'] ?? [];
        unset($validated['attendance_ids']);

        DB::transaction(function () use ($validated, $paidAmount, $advanceAdjusted, $salaryAmount, $payer, $advancePaid, $attendanceIds): void {
            $balanceService = app(CrmBalanceService::class);
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);

            if ($advancePaid && $advanceAdjusted > (float) $labour->advance_amt) {
                throw ValidationException::withMessages([
                    'advance_adjusted' => 'Advance adjusted amount (Rs ' . number_format($advanceAdjusted, 2) . ') cannot exceed available advance balance (Rs ' . number_format($labour->advance_amt, 2) . ').',
                ]);
            }

            if ($advancePaid && $advanceAdjusted > $salaryAmount) {
                throw ValidationException::withMessages([
                    'advance_adjusted' => 'Advance adjusted amount (Rs ' . number_format($advanceAdjusted, 2) . ') cannot exceed calculated salary (Rs ' . number_format($salaryAmount, 2) . ').',
                ]);
            }

            $netPayable = round(max(0.0, $salaryAmount - $advanceAdjusted), 2);

            if ($paidAmount > $netPayable) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount (Rs ' . number_format($paidAmount, 2) . ') cannot exceed net payable salary (Rs ' . number_format($netPayable, 2) . ').',
                ]);
            }

            if ($paidAmount > 0) {
                $balanceService->debitUserWallet($payer->id, $paidAmount, 'Labour Salary payment for ' . $labour->name);
            }

            if ($advanceAdjusted > 0) {
                $balanceService->adjustLabourAdvance((int) $labour->id, -$advanceAdjusted);
            }

            $validated['paid_by'] = $payer->id;
            $validated['remaining_amount'] = round(max(0.0, $netPayable - $paidAmount), 2);
            $validated['status'] = $paidAmount >= $netPayable ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');

            if (! Schema::hasColumn('labour_salaries', 'advance_paid')) {
                unset($validated['advance_paid']);
            }

            $labourSalary = LabourSalary::create($validated);

            // Explicit attendance linking within transaction
            if (empty($attendanceIds) && ! empty($validated['salary_period_start']) && ! empty($validated['salary_period_end'])) {
                $summary = LabourAttendanceController::calculatePeriodSummary(
                    $labour,
                    $validated['salary_period_start'],
                    $validated['salary_period_end']
                );
                $attendanceIds = $summary['attendance_ids'] ?? [];
            }

            if ($labourSalary->status === 'paid' && ! empty($attendanceIds)) {
                $labourSalary->linkAttendances($attendanceIds);
            }

            if ($advanceAdjusted > 0) {
                $advanceHistoryPayload = [
                    'labour_id' => $labour->id,
                    'labour_salary_id' => $labourSalary->id,
                    'amount' => $advanceAdjusted,
                    'entry_type' => 'settle',
                    'notes' => 'Advance adjusted against salary period ' . ($validated['salary_period_start'] ?? '') . ' to ' . ($validated['salary_period_end'] ?? ''),
                    'user_id' => $payer->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ];

                if (Schema::hasColumn('advance_history', 'payment_method_id') && isset($validated['payment_method_id'])) {
                    $advanceHistoryPayload['payment_method_id'] = $validated['payment_method_id'];
                }

                AdvanceHistory::create($advanceHistoryPayload);
            }

            if ($paidAmount > 0) {
                Wallet::query()->create([
                    'user_id' => $payer->id,
                    'client_id' => 0,
                    'project_id' => 0,
                    'amount' => round($paidAmount, 2),
                    'payment_mode' => $validated['payment_method_id'] ?? 1,
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'transfer_type' => 1,
                    'source_type' => 'labour_salary',
                    'source_id' => $labourSalary->id,
                    'description' => 'Paid Labour Salary to ' . $labour->name,
                    'created_by' => $payer->id,
                    'current_date' => $validated['payment_date'] ?? now(),
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);
            }

            $this->syncSalaryExpense(
                $labourSalary,
                $labour,
                (int) $payer->id,
                $paidAmount,
                $advanceAdjusted,
                isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
                $validated['payment_date'] ?? now()
            );
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary recorded and payer wallet debited successfully.');
    }

    public function edit(LabourSalary $labourSalary): View
    {
        $labours = Labour::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $payerWalletBalance = (float) (Auth::user()->wallet ?? 0);

        return view('pages.labour_salaries.edit', compact('labourSalary', 'labours', 'paymentMethods', 'payerWalletBalance'));
    }

    public function update(Request $request, LabourSalary $labourSalary): RedirectResponse
    {
        $validated = $this->validateLabourSalary($request, $labourSalary);
        $payer = Auth::user();
        $oldPaidAmount = round((float) $labourSalary->paid_amount, 2);
        $newPaidAmount = round((float) $validated['paid_amount'], 2);
        $diff = round($newPaidAmount - $oldPaidAmount, 2);

        $oldAdvanceAdjusted = round((float) $labourSalary->advance_adjusted, 2);
        $newAdvanceAdjusted = round((float) ($validated['advance_adjusted'] ?? 0), 2);
        $advanceDiff = round($newAdvanceAdjusted - $oldAdvanceAdjusted, 2);

        $salaryAmount = round((float) $validated['salary_amount'], 2);
        $advancePaid = (bool) ($validated['advance_paid'] ?? false);
        $attendanceIds = $validated['attendance_ids'] ?? null;
        unset($validated['attendance_ids']);

        if ($advancePaid && $newAdvanceAdjusted > $salaryAmount) {
            throw ValidationException::withMessages([
                'advance_adjusted' => 'Advance adjusted amount (Rs ' . number_format($newAdvanceAdjusted, 2) . ') cannot exceed calculated salary (Rs ' . number_format($salaryAmount, 2) . ').',
            ]);
        }

        $netPayable = round(max(0.0, $salaryAmount - $newAdvanceAdjusted), 2);

        if ($newPaidAmount > $netPayable) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount (Rs ' . number_format($newPaidAmount, 2) . ') cannot exceed net payable salary (Rs ' . number_format($netPayable, 2) . ').',
            ]);
        }

        DB::transaction(function () use ($labourSalary, $validated, $diff, $advanceDiff, $newAdvanceAdjusted, $payer, $newPaidAmount, $netPayable, $attendanceIds): void {
            $balanceService = app(CrmBalanceService::class);
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);

            if ($advanceDiff > 0 && $advanceDiff > (float) $labour->advance_amt) {
                throw ValidationException::withMessages([
                    'advance_adjusted' => 'Additional advance adjustment exceeds current available advance balance.',
                ]);
            }

            if ($diff > 0) {
                $balanceService->debitUserWallet($payer->id, $diff, 'Updated Labour Salary payment difference');
            } elseif ($diff < 0) {
                $balanceService->creditUserWallet($payer->id, abs($diff), 'Reversal of Labour Salary payment difference');
            }

            if ($advanceDiff != 0.0) {
                // Adjust advance balance by the difference
                $balanceService->adjustLabourAdvance((int) $labour->id, -$advanceDiff);

                $history = AdvanceHistory::query()
                    ->where('labour_salary_id', $labourSalary->id)
                    ->first();

                if ($newAdvanceAdjusted > 0) {
                    $historyPayload = [
                        'amount' => $newAdvanceAdjusted,
                    ];

                    if (Schema::hasColumn('advance_history', 'payment_method_id') && isset($validated['payment_method_id'])) {
                        $historyPayload['payment_method_id'] = $validated['payment_method_id'];
                    }

                    if ($history) {
                        $history->update($historyPayload);
                    } else {
                        AdvanceHistory::create($historyPayload + [
                            'labour_id' => $labour->id,
                            'labour_salary_id' => $labourSalary->id,
                            'entry_type' => 'settle',
                            'notes' => 'Advance adjusted against salary period ' . ($validated['salary_period_start'] ?? '') . ' to ' . ($validated['salary_period_end'] ?? ''),
                            'user_id' => $payer->id,
                            'current_date' => now()->toDateString(),
                            'current_time' => now()->format('H:i:s'),
                        ]);
                    }
                } elseif ($history) {
                    $history->delete();
                }
            }

            $validated['remaining_amount'] = round(max(0.0, $netPayable - $newPaidAmount), 2);
            $validated['status'] = $newPaidAmount >= $netPayable ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'pending');

            if (! Schema::hasColumn('labour_salaries', 'advance_paid')) {
                unset($validated['advance_paid']);
            }

            $labourSalary->update($validated);

            if ($attendanceIds !== null && $labourSalary->status === 'paid') {
                $labourSalary->linkAttendances($attendanceIds);
            }

            if ($newPaidAmount > 0) {
                Wallet::query()
                    ->updateOrCreate([
                        'source_type' => 'labour_salary',
                        'source_id' => $labourSalary->id,
                    ], [
                        'user_id' => $payer->id,
                        'client_id' => 0,
                        'project_id' => 0,
                        'amount' => round($newPaidAmount, 2),
                        'payment_mode' => $validated['payment_method_id'] ?? 1,
                        'payment_method_id' => $validated['payment_method_id'] ?? null,
                        'transfer_type' => 1,
                        'description' => 'Paid Labour Salary to ' . $labour->name,
                        'created_by' => $payer->id,
                        'current_date' => $validated['payment_date'] ?? now(),
                        'active_status' => 1,
                        'delete_status' => 0,
                    ]);
            } else {
                Wallet::query()
                    ->where('source_type', 'labour_salary')
                    ->where('source_id', $labourSalary->id)
                    ->delete();
            }

            $this->syncSalaryExpense(
                $labourSalary,
                $labour,
                (int) $payer->id,
                $newPaidAmount,
                $newAdvanceAdjusted,
                isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
                $validated['payment_date'] ?? now()
            );
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary updated successfully.');
    }

    public function destroy(LabourSalary $labourSalary): RedirectResponse
    {
        DB::transaction(function () use ($labourSalary): void {
            $paidAmount = (float) $labourSalary->paid_amount;
            $advanceAdjusted = (float) $labourSalary->advance_adjusted;
            $balanceService = app(CrmBalanceService::class);

            if ($paidAmount > 0 && $labourSalary->paid_by) {
                $balanceService->creditUserWallet($labourSalary->paid_by, $paidAmount, 'Refund deleted Labour Salary #' . $labourSalary->id);
            }

            if ($advanceAdjusted > 0 && $labourSalary->labour_id) {
                // Revert advance balance
                $balanceService->adjustLabourAdvance((int) $labourSalary->labour_id, $advanceAdjusted);

                AdvanceHistory::query()
                    ->where('labour_salary_id', $labourSalary->id)
                    ->delete();
            }

            Wallet::query()
                ->where('source_type', 'labour_salary')
                ->where('source_id', $labourSalary->id)
                ->delete();

            if (Schema::hasTable('expenses')) {
                Expense::query()
                    ->where('source_type', 'labour_salary')
                    ->where('source_id', $labourSalary->id)
                    ->delete();
            }

            $labourSalary->delete();
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary deleted and balances restored successfully.');
    }

    private function syncSalaryExpense(
        LabourSalary $labourSalary,
        Labour $labour,
        int $payerId,
        float $paidAmount,
        float $advanceAdjusted,
        ?int $paymentMethodId,
        $paymentDate
    ): void {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        $salaryAmount = round((float) $labourSalary->salary_amount, 2);
        $cashPaid = round($paidAmount, 2);
        $advanceAdj = round($advanceAdjusted, 2);
        $totalPaid = round($cashPaid + $advanceAdj, 2);
        $unpaidAmt = round(max(0, $salaryAmount - $totalPaid), 2);

        $expense = Expense::query()
            ->where('source_type', 'labour_salary')
            ->where('source_id', $labourSalary->id)
            ->first();

        if ($salaryAmount <= 0 && $totalPaid <= 0) {
            $expense?->delete();
            return;
        }

        $category = Category::query()
            ->where('name', 'LABOUR SALARY')
            ->first()
            ?? Category::query()->where('name', 'like', '%LABOUR SALARY%')->first()
            ?? Category::query()->where('name', 'like', '%SALARY%')->first();

        $mainCategoryId = $category?->main_category_id
            ?? MainCategory::query()->where('name', 'CIVIL')->value('id')
            ?? MainCategory::query()->first()?->id;

        $desc = 'Paid Labour Salary to ' . $labour->name . ($advanceAdjusted > 0 ? ' (Rs ' . number_format($advanceAdjusted, 2) . ' advance adjusted)' : '');

        $projectId = $this->resolveSalaryProjectId($labourSalary);

        $payload = [
            'user_id' => $payerId,
            'labour_id' => $labour->id,
            'project_id' => $projectId,
            'main_category_id' => $mainCategoryId,
            'category_id' => $category?->id,
            'amount' => $salaryAmount,
            'paid_amt' => $totalPaid,
            'unpaid_amt' => $unpaidAmt,
            'extra_amt' => 0.0,
            'current_date' => $paymentDate,
            'payment_method_id' => $paymentMethodId,
            'payment_mode' => $paymentMethodId,
            'description' => $desc,
            'is_advance' => $advanceAdjusted > 0 ? 1 : null,
            'source_type' => 'labour_salary',
            'source_id' => $labourSalary->id,
        ];

        if ($expense) {
            $expense->update($payload + ['editedBy' => $payerId]);
        } else {
            Expense::create($payload);
        }
    }

    private function resolveSalaryProjectId(LabourSalary $labourSalary): ?int
    {
        if (! Schema::hasTable('labour_assignments')) {
            return null;
        }

        // A. For every linked LabourAttendance: resolve project using LabourAssignment::activeForDate(attendance_date)
        $attendances = $labourSalary->attendances()->get();
        $projectCounts = [];
        $projectLatestDate = [];

        foreach ($attendances as $att) {
            $attDate = $att->attendance_date ? Carbon::parse($att->attendance_date)->toDateString() : null;
            if (! $attDate) {
                continue;
            }

            $projectId = LabourAssignment::query()
                ->where('labour_id', $labourSalary->labour_id)
                ->activeForDate($attDate)
                ->value('project_id');

            if ($projectId) {
                $pid = (int) $projectId;
                $projectCounts[$pid] = ($projectCounts[$pid] ?? 0) + 1;
                if (! isset($projectLatestDate[$pid]) || $attDate > $projectLatestDate[$pid]) {
                    $projectLatestDate[$pid] = $attDate;
                }
            }
        }

        // B. If all resolved attendances have one project: return that project.
        if (count($projectCounts) === 1) {
            return (int) array_key_first($projectCounts);
        }

        // C & D. If multiple projects: count attendance dates per project.
        // Select project with highest attendance count. If tied: select project associated with latest attendance date.
        if (count($projectCounts) > 1) {
            $bestProjectId = null;
            $bestCount = -1;
            $bestLatestDate = null;

            foreach ($projectCounts as $pid => $count) {
                $latestDate = $projectLatestDate[$pid];
                if ($count > $bestCount) {
                    $bestCount = $count;
                    $bestLatestDate = $latestDate;
                    $bestProjectId = $pid;
                } elseif ($count === $bestCount) {
                    if ($bestLatestDate === null || $latestDate > $bestLatestDate) {
                        $bestLatestDate = $latestDate;
                        $bestProjectId = $pid;
                    }
                }
            }

            return $bestProjectId ? (int) $bestProjectId : null;
        }

        // E. If no attendance project can be resolved: look for assignment overlapping salary period.
        $startDate = $labourSalary->salary_period_start ? Carbon::parse($labourSalary->salary_period_start)->toDateString() : null;
        $endDate = $labourSalary->salary_period_end ? Carbon::parse($labourSalary->salary_period_end)->toDateString() : null;

        if ($startDate && $endDate) {
            $overlappingProjectId = LabourAssignment::query()
                ->where('labour_id', $labourSalary->labour_id)
                ->whereNotNull('project_id')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereDate('start_date', '<=', $endDate)
                      ->whereDate('end_date', '>=', $startDate);
                })
                ->orderByDesc('end_date')
                ->orderByDesc('id')
                ->value('project_id');

            if ($overlappingProjectId) {
                return (int) $overlappingProjectId;
            }
        }

        // F. If still unresolved: use latest LabourAssignment project.
        $latestProjectId = LabourAssignment::query()
            ->where('labour_id', $labourSalary->labour_id)
            ->whereNotNull('project_id')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->value('project_id');

        if ($latestProjectId) {
            return (int) $latestProjectId;
        }

        // G. If no assignment exists: return null.
        return null;
    }

    private function validateLabourSalary(Request $request, ?LabourSalary $labourSalary = null): array
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'salary_period_start' => ['nullable', 'date'],
            'salary_period_end' => ['nullable', 'date'],
            'salary_amount' => ['required', 'numeric', 'min:0.01'],
            'advance_paid' => ['nullable'],
            'advance_adjusted' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['paid', 'partial', 'pending'])],
            'attendance_ids' => ['nullable'],
            'attendance_ids.*' => ['integer'],
        ]);

        if (isset($validated['attendance_ids'])) {
            if (is_string($validated['attendance_ids'])) {
                $decoded = json_decode($validated['attendance_ids'], true);
                $validated['attendance_ids'] = is_array($decoded)
                    ? $decoded
                    : array_filter(array_map('trim', explode(',', $validated['attendance_ids'])));
            }
            $validated['attendance_ids'] = array_values(array_unique(array_filter(array_map('intval', (array) $validated['attendance_ids']))));
        }

        $hasAdvancePaidKey = $request->has('advance_paid');
        $advancePaid = $hasAdvancePaidKey
            ? $request->boolean('advance_paid')
            : ((float) ($request->input('advance_adjusted') ?? 0) > 0);

        $validated['advance_paid'] = $advancePaid;

        if (! $advancePaid) {
            // Manipulation-proof: if Advance Paid is OFF, advance_adjusted is ALWAYS 0.0
            $validated['advance_adjusted'] = 0.0;
        } else {
            $validated['advance_adjusted'] = round((float) ($validated['advance_adjusted'] ?? 0), 2);
        }

        return $validated;
    }
}
