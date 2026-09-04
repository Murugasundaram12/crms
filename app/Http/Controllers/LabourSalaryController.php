<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Labour;
use App\Models\LabourSalary;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($validated, $paidAmount, $advanceAdjusted, $salaryAmount, $payer): void {
            $balanceService = app(CrmBalanceService::class);
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);

            if ($advanceAdjusted > (float) $labour->advance_amt) {
                throw ValidationException::withMessages([
                    'advance_adjusted' => 'Advance adjusted amount (Rs ' . number_format($advanceAdjusted, 2) . ') cannot exceed available advance balance (Rs ' . number_format($labour->advance_amt, 2) . ').',
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

            $labourSalary = LabourSalary::create($validated);

            if ($advanceAdjusted > 0) {
                AdvanceHistory::create([
                    'labour_id' => $labour->id,
                    'labour_salary_id' => $labourSalary->id,
                    'amount' => $advanceAdjusted,
                    'entry_type' => 'settle',
                    'notes' => 'Advance adjusted against salary period ' . ($validated['salary_period_start'] ?? '') . ' to ' . ($validated['salary_period_end'] ?? ''),
                    'user_id' => $payer->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }

            if ($paidAmount > 0) {
                Wallet::query()->create([
                    'user_id' => $payer->id,
                    'client_id' => 0,
                    'project_id' => 0,
                    'amount' => (int) round($paidAmount),
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
        $netPayable = round(max(0.0, $salaryAmount - $newAdvanceAdjusted), 2);

        if ($newPaidAmount > $netPayable) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount (Rs ' . number_format($newPaidAmount, 2) . ') cannot exceed net payable salary (Rs ' . number_format($netPayable, 2) . ').',
            ]);
        }

        DB::transaction(function () use ($labourSalary, $validated, $diff, $advanceDiff, $newAdvanceAdjusted, $payer, $newPaidAmount, $netPayable): void {
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

                AdvanceHistory::query()
                    ->where('labour_salary_id', $labourSalary->id)
                    ->update([
                        'amount' => $newAdvanceAdjusted,
                    ]);
            }

            $validated['remaining_amount'] = round(max(0.0, $netPayable - $newPaidAmount), 2);
            $validated['status'] = $newPaidAmount >= $netPayable ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'pending');

            $labourSalary->update($validated);

            Wallet::query()
                ->where('source_type', 'labour_salary')
                ->where('source_id', $labourSalary->id)
                ->update([
                    'amount' => (int) round($newPaidAmount),
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'description' => 'Paid Labour Salary to ' . $labour->name,
                ]);
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

            $labourSalary->delete();
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary deleted and balances restored successfully.');
    }

    private function validateLabourSalary(Request $request, ?LabourSalary $labourSalary = null): array
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'salary_period_start' => ['nullable', 'date'],
            'salary_period_end' => ['nullable', 'date'],
            'salary_amount' => ['required', 'numeric', 'min:0.01'],
            'advance_adjusted' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['paid', 'partial', 'pending'])],
        ]);

        $validated['advance_adjusted'] = (float) ($validated['advance_adjusted'] ?? 0);

        return $validated;
    }
}
