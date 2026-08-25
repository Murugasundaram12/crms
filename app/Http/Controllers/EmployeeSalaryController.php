<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSalary;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use App\Services\EmployeePayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request): View
    {
        $employeeSalaryQuery = EmployeeSalary::query()->with(['user', 'paymentMethod', 'payer']);
        $this->applySearchFilter($employeeSalaryQuery, $request);
        $this->applyDateFilter($employeeSalaryQuery, $request);

        $employeeSalaries = $employeeSalaryQuery->latest()->paginate(10)->withQueryString();

        return view('pages.employee_salaries.index', compact('employeeSalaries'));
    }

    public function create(): View
    {
        $employeeUsers = $this->getEligibleEmployeeUsers();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $payerWalletBalance = (float) (Auth::user()->wallet ?? 0);

        return view('pages.employee_salaries.create', compact('employeeUsers', 'paymentMethods', 'payerWalletBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $this->validateEmployeeSalaryData($request);

        $payer = Auth::user();
        $paidAmount = (float) $validatedData['paid_amount'];

        DB::transaction(function () use ($validatedData, $paidAmount, $payer): void {
            $balanceService = app(CrmBalanceService::class);

            if ($paidAmount > 0) {
                $balanceService->debitUserWallet($payer->id, $paidAmount, 'Employee Salary payment for user #' . $validatedData['user_id']);
            }

            $validatedData['paid_by'] = $payer->id;
            $validatedData['name'] = User::query()->whereKey($validatedData['user_id'])->value('name');
            $validatedData['salary'] = $validatedData['salary_amount'];

            $salaryRecord = EmployeeSalary::create($validatedData);

            if ($paidAmount > 0) {
                Wallet::query()->create([
                    'user_id' => $payer->id,
                    'client_id' => 0,
                    'project_id' => 0,
                    'amount' => (int) round($paidAmount),
                    'payment_mode' => $validatedData['payment_method_id'] ?? 1,
                    'payment_method_id' => $validatedData['payment_method_id'] ?? null,
                    'transfer_type' => 1,
                    'source_type' => 'employee_salary',
                    'source_id' => $salaryRecord->id,
                    'description' => 'Paid Employee Salary to ' . $validatedData['name'] . ' for period ' . ($validatedData['salary_period'] ?? 'N/A'),
                    'created_by' => $payer->id,
                    'current_date' => $validatedData['payment_date'] ?? now(),
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);
            }
        });

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary saved and payer wallet debited successfully.');
    }

    public function edit(EmployeeSalary $employeeSalary): View
    {
        $employeeUsers = $this->getEligibleEmployeeUsers();
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $payerWalletBalance = (float) (Auth::user()->wallet ?? 0);

        return view('pages.employee_salaries.edit', compact('employeeSalary', 'employeeUsers', 'paymentMethods', 'payerWalletBalance'));
    }

    public function update(Request $request, EmployeeSalary $employeeSalary): RedirectResponse
    {
        $validatedData = $this->validateEmployeeSalaryData($request, $employeeSalary);
        $payer = Auth::user();
        $oldPaidAmount = (float) $employeeSalary->paid_amount;
        $newPaidAmount = (float) $validatedData['paid_amount'];
        $diff = $newPaidAmount - $oldPaidAmount;

        DB::transaction(function () use ($employeeSalary, $validatedData, $diff, $payer, $newPaidAmount): void {
            $balanceService = app(CrmBalanceService::class);

            if ($diff > 0) {
                $balanceService->debitUserWallet($payer->id, $diff, 'Updated Employee Salary payment difference');
            } elseif ($diff < 0) {
                $balanceService->creditUserWallet($payer->id, abs($diff), 'Reversal of Employee Salary payment difference');
            }

            $validatedData['name'] = User::query()->whereKey($validatedData['user_id'])->value('name');
            $validatedData['salary'] = $validatedData['salary_amount'];

            $employeeSalary->update($validatedData);

            Wallet::query()
                ->where('source_type', 'employee_salary')
                ->where('source_id', $employeeSalary->id)
                ->update([
                    'amount' => (int) round($newPaidAmount),
                    'payment_method_id' => $validatedData['payment_method_id'] ?? null,
                    'description' => 'Paid Employee Salary to ' . $validatedData['name'] . ' for period ' . ($validatedData['salary_period'] ?? 'N/A'),
                ]);
        });

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary updated successfully.');
    }

    public function destroy(EmployeeSalary $employeeSalary): RedirectResponse
    {
        DB::transaction(function () use ($employeeSalary): void {
            $paidAmount = (float) $employeeSalary->paid_amount;
            if ($paidAmount > 0 && $employeeSalary->paid_by) {
                app(CrmBalanceService::class)->creditUserWallet($employeeSalary->paid_by, $paidAmount, 'Refund deleted Employee Salary #' . $employeeSalary->id);
            }

            Wallet::query()
                ->where('source_type', 'employee_salary')
                ->where('source_id', $employeeSalary->id)
                ->delete();

            $employeeSalary->delete();
        });

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary deleted and payer wallet refunded successfully.');
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'salary_period' => ['required', 'string', 'max:50'],
            'custom_monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'overtime_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee = User::findOrFail($request->integer('user_id'));
        $period = $request->string('salary_period')->toString();
        $customMonthlySalary = $request->filled('custom_monthly_salary') ? (float) $request->input('custom_monthly_salary') : null;
        $otherDeductions = (float) $request->input('other_deductions', 0);
        $overtimeAmount = (float) $request->input('overtime_amount', 0);

        $payrollService = app(EmployeePayrollService::class);
        $breakdown = $payrollService->calculateMonthlySalary(
            $employee,
            $period,
            $customMonthlySalary,
            $otherDeductions,
            $overtimeAmount
        );

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    private function applySearchFilter($employeeSalaryQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();
        if ($searchTerm === '') {
            return;
        }

        $employeeSalaryQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('salary_period', 'like', "%{$searchTerm}%")
                ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%"));
        });
    }

    private function applyDateFilter($employeeSalaryQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $employeeSalaryQuery->whereDate('payment_date', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $employeeSalaryQuery->whereDate('payment_date', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateEmployeeSalaryData(Request $request, ?EmployeeSalary $employeeSalary = null): array
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'salary_period' => ['required', 'string', 'max:50'],
            'salary_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'salary_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['paid', 'partial', 'pending'])],

            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'working_days' => ['nullable', 'integer', 'min:0'],
            'present_days' => ['nullable', 'numeric', 'min:0'],
            'half_days' => ['nullable', 'integer', 'min:0'],
            'paid_leave_days' => ['nullable', 'integer', 'min:0'],
            'unpaid_leave_days' => ['nullable', 'integer', 'min:0'],
            'absent_days' => ['nullable', 'integer', 'min:0'],
            'per_day_salary' => ['nullable', 'numeric', 'min:0'],
            'gross_salary' => ['nullable', 'numeric', 'min:0'],
            'half_day_deduction' => ['nullable', 'numeric', 'min:0'],
            'unpaid_leave_deduction' => ['nullable', 'numeric', 'min:0'],
            'absent_deduction' => ['nullable', 'numeric', 'min:0'],
            'attendance_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'overtime_amount' => ['nullable', 'numeric', 'min:0'],
            'net_salary' => ['nullable', 'numeric', 'min:0'],
            'calculated_at' => ['nullable', 'date'],
        ]);

        $employee = User::find($validated['user_id']);
        if ($employee && (empty($validated['working_days']) || ! isset($validated['net_salary']))) {
            $payrollService = app(EmployeePayrollService::class);
            $customMonthly = isset($validated['monthly_salary']) && $validated['monthly_salary'] > 0
                ? (float) $validated['monthly_salary']
                : null;
            $otherDed = (float) ($validated['other_deductions'] ?? 0);
            $otAmt = (float) ($validated['overtime_amount'] ?? 0);

            $breakdown = $payrollService->calculateMonthlySalary(
                $employee,
                $validated['salary_period'],
                $customMonthly,
                $otherDed,
                $otAmt
            );

            $validated['monthly_salary'] = $breakdown['monthly_salary'];
            $validated['working_days'] = $breakdown['working_days'];
            $validated['present_days'] = $breakdown['present_days'];
            $validated['half_days'] = $breakdown['half_days'];
            $validated['paid_leave_days'] = $breakdown['paid_leave_days'];
            $validated['unpaid_leave_days'] = $breakdown['unpaid_leave_days'];
            $validated['absent_days'] = $breakdown['absent_days'];
            $validated['per_day_salary'] = $breakdown['per_day_salary'];
            $validated['gross_salary'] = $breakdown['gross_salary'];
            $validated['half_day_deduction'] = $breakdown['half_day_deduction'];
            $validated['unpaid_leave_deduction'] = $breakdown['unpaid_leave_deduction'];
            $validated['absent_deduction'] = $breakdown['absent_deduction'];
            $validated['attendance_deduction'] = $breakdown['attendance_deduction'];
            $validated['other_deductions'] = $breakdown['other_deductions'];
            $validated['overtime_amount'] = $breakdown['overtime_amount'];
            $validated['net_salary'] = $breakdown['net_salary'];
            $validated['calculated_at'] = $breakdown['calculated_at'];

            if (! isset($request->salary_amount)) {
                $validated['salary_amount'] = $breakdown['net_salary'];
            }
        }

        $salaryAmount = (float) $validated['salary_amount'];
        $paidAmount = (float) $validated['paid_amount'];

        if ($paidAmount > $salaryAmount && $salaryAmount > 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paid_amount' => 'Paid amount cannot be greater than salary amount.',
            ]);
        }

        $validated['remaining_amount'] = round(max(0, $salaryAmount - $paidAmount), 2);
        $validated['salary_type'] = $validated['salary_type'] ?? 'monthly';
        $validated['status'] = $validated['status'] ?? ($paidAmount >= $salaryAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'));

        return $validated;
    }

    private function getEligibleEmployeeUsers()
    {
        return User::query()
            ->where(function ($queryBuilder) {
                $queryBuilder->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get();
    }
}
