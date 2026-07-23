<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourSalary;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    public function create(): View
    {
        $labours = Labour::query()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $payerWalletBalance = (float) (Auth::user()->wallet ?? 0);

        return view('pages.labour_salaries.create', compact('labours', 'paymentMethods', 'payerWalletBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLabourSalary($request);

        $payer = Auth::user();
        $paidAmount = (float) $validated['paid_amount'];

        DB::transaction(function () use ($validated, $paidAmount, $payer): void {
            $balanceService = app(CrmBalanceService::class);

            if ($paidAmount > 0) {
                $balanceService->debitUserWallet($payer->id, $paidAmount, 'Labour Salary payment for labour #' . $validated['labour_id']);
            }

            $validated['paid_by'] = $payer->id;
            $labourSalary = LabourSalary::create($validated);

            if ($paidAmount > 0) {
                $labourName = Labour::query()->whereKey($validated['labour_id'])->value('name');

                Wallet::query()->create([
                    'user_id' => $payer->id,
                    'amount' => (int) round($paidAmount),
                    'payment_mode' => $validated['payment_method_id'] ?? 1,
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'transfer_type' => 1,
                    'source_type' => 'labour_salary',
                    'source_id' => $labourSalary->id,
                    'description' => 'Paid Labour Salary to ' . $labourName,
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
        $oldPaidAmount = (float) $labourSalary->paid_amount;
        $newPaidAmount = (float) $validated['paid_amount'];
        $diff = $newPaidAmount - $oldPaidAmount;

        DB::transaction(function () use ($labourSalary, $validated, $diff, $payer, $newPaidAmount): void {
            $balanceService = app(CrmBalanceService::class);

            if ($diff > 0) {
                $balanceService->debitUserWallet($payer->id, $diff, 'Updated Labour Salary payment difference');
            } elseif ($diff < 0) {
                $balanceService->creditUserWallet($payer->id, abs($diff), 'Reversal of Labour Salary payment difference');
            }

            $labourSalary->update($validated);

            $labourName = Labour::query()->whereKey($validated['labour_id'])->value('name');

            Wallet::query()
                ->where('source_type', 'labour_salary')
                ->where('source_id', $labourSalary->id)
                ->update([
                    'amount' => (int) round($newPaidAmount),
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'description' => 'Paid Labour Salary to ' . $labourName,
                ]);
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary updated successfully.');
    }

    public function destroy(LabourSalary $labourSalary): RedirectResponse
    {
        DB::transaction(function () use ($labourSalary): void {
            $paidAmount = (float) $labourSalary->paid_amount;
            if ($paidAmount > 0 && $labourSalary->paid_by) {
                app(CrmBalanceService::class)->creditUserWallet($labourSalary->paid_by, $paidAmount, 'Refund deleted Labour Salary #' . $labourSalary->id);
            }

            Wallet::query()
                ->where('source_type', 'labour_salary')
                ->where('source_id', $labourSalary->id)
                ->delete();

            $labourSalary->delete();
        });

        return redirect()->route('labour-salaries.index')->with('success', 'Labour salary deleted and payer wallet refunded successfully.');
    }

    private function validateLabourSalary(Request $request, ?LabourSalary $labourSalary = null): array
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'salary_period_start' => ['nullable', 'date'],
            'salary_period_end' => ['nullable', 'date'],
            'salary_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['paid', 'partial', 'pending'])],
        ]);

        $salaryAmount = (float) $validated['salary_amount'];
        $paidAmount = (float) $validated['paid_amount'];

        if ($paidAmount > $salaryAmount) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paid_amount' => 'Paid amount cannot be greater than salary amount.',
            ]);
        }

        $validated['remaining_amount'] = round($salaryAmount - $paidAmount, 2);
        $validated['status'] = $validated['status'] ?? ($paidAmount >= $salaryAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'));

        return $validated;
    }
}
