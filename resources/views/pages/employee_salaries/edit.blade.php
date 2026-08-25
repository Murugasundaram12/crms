@extends('layouts.app')

@section('title', 'Edit Employee Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Employee Salary</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-salaries.index') }}">Employee Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Salary</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block">Your Current Payer Wallet Balance:</span>
                    <h4 class="mb-0 text-success">Rs {{ number_format($payerWalletBalance, 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('employee-salaries.update', $employeeSalary) }}" method="POST" id="salaryForm">
                @csrf
                @method('PUT')

                <!-- Hidden Breakdown Fields -->
                <input type="hidden" name="monthly_salary" id="field_monthly_salary" value="{{ old('monthly_salary', $employeeSalary->monthly_salary) }}">
                <input type="hidden" name="working_days" id="field_working_days" value="{{ old('working_days', $employeeSalary->working_days) }}">
                <input type="hidden" name="present_days" id="field_present_days" value="{{ old('present_days', $employeeSalary->present_days) }}">
                <input type="hidden" name="half_days" id="field_half_days" value="{{ old('half_days', $employeeSalary->half_days) }}">
                <input type="hidden" name="paid_leave_days" id="field_paid_leave_days" value="{{ old('paid_leave_days', $employeeSalary->paid_leave_days) }}">
                <input type="hidden" name="unpaid_leave_days" id="field_unpaid_leave_days" value="{{ old('unpaid_leave_days', $employeeSalary->unpaid_leave_days) }}">
                <input type="hidden" name="absent_days" id="field_absent_days" value="{{ old('absent_days', $employeeSalary->absent_days) }}">
                <input type="hidden" name="per_day_salary" id="field_per_day_salary" value="{{ old('per_day_salary', $employeeSalary->per_day_salary) }}">
                <input type="hidden" name="gross_salary" id="field_gross_salary" value="{{ old('gross_salary', $employeeSalary->gross_salary) }}">
                <input type="hidden" name="half_day_deduction" id="field_half_day_deduction" value="{{ old('half_day_deduction', $employeeSalary->half_day_deduction) }}">
                <input type="hidden" name="unpaid_leave_deduction" id="field_unpaid_leave_deduction" value="{{ old('unpaid_leave_deduction', $employeeSalary->unpaid_leave_deduction) }}">
                <input type="hidden" name="absent_deduction" id="field_absent_deduction" value="{{ old('absent_deduction', $employeeSalary->absent_deduction) }}">
                <input type="hidden" name="attendance_deduction" id="field_attendance_deduction" value="{{ old('attendance_deduction', $employeeSalary->attendance_deduction) }}">
                <input type="hidden" name="net_salary" id="field_net_salary" value="{{ old('net_salary', $employeeSalary->net_salary) }}">

                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <label class="form-label required">Employee</label>
                        <select name="user_id" id="user_id_select" class="form-select @error('user_id') is-invalid @enderror" required>
                            @foreach ($employeeUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', $employeeSalary->user_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Salary Period / Month</label>
                        <input type="text" name="salary_period" id="salary_period_input" class="form-control @error('salary_period') is-invalid @enderror" value="{{ old('salary_period', $employeeSalary->salary_period) }}" required>
                        @error('salary_period')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button type="button" id="btn_calculate_payroll" class="btn btn-outline-primary w-100">
                            <i class="ti ti-calculator me-1"></i>Recalculate Payroll
                        </button>
                    </div>

                    <!-- Interactive Payroll Breakdown Card -->
                    <div class="col-12" id="payroll_summary_container">
                        <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 shadow-none">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 text-primary"><i class="ti ti-report-analytics me-1"></i>Attendance Payroll Breakdown</h6>
                                    <span class="badge bg-primary" id="badge_period_text">{{ $employeeSalary->salary_period }}</span>
                                </div>
                                <div class="row g-3 text-dark">
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Working Days</small>
                                        <strong id="disp_working_days">{{ $employeeSalary->working_days ?? 0 }}</strong>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Present Days</small>
                                        <strong class="text-success" id="disp_present_days">{{ $employeeSalary->present_days ?? 0 }}</strong>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Half Days</small>
                                        <strong class="text-warning" id="disp_half_days">{{ $employeeSalary->half_days ?? 0 }}</strong>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Paid Leave</small>
                                        <strong class="text-info" id="disp_paid_leave">{{ $employeeSalary->paid_leave_days ?? 0 }}</strong>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Unpaid Leave</small>
                                        <strong class="text-danger" id="disp_unpaid_leave">{{ $employeeSalary->unpaid_leave_days ?? 0 }}</strong>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <small class="text-muted d-block">Absent Days</small>
                                        <strong class="text-danger" id="disp_absent_days">{{ $employeeSalary->absent_days ?? 0 }}</strong>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="row g-3">
                                    <div class="col-12 col-md-3">
                                        <small class="text-muted d-block">Base Monthly Salary</small>
                                        <span class="fs-6 fw-semibold" id="disp_monthly_salary">Rs {{ number_format($employeeSalary->monthly_salary ?? $employeeSalary->salary, 2) }}</span>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <small class="text-muted d-block">Per Day Salary</small>
                                        <span class="fs-6 fw-semibold" id="disp_per_day_salary">Rs {{ number_format($employeeSalary->per_day_salary ?? 0, 2) }}</span>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <small class="text-muted d-block">Attendance Deductions</small>
                                        <span class="fs-6 fw-semibold text-danger" id="disp_attendance_deduction">Rs {{ number_format($employeeSalary->attendance_deduction ?? 0, 2) }}</span>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <small class="text-muted d-block">Net Calculated Salary</small>
                                        <span class="fs-5 fw-bold text-success" id="disp_net_salary">Rs {{ number_format($employeeSalary->net_salary ?? $employeeSalary->salary_amount, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Salary Amount (Net Payable)</label>
                        <input type="number" step="0.01" name="salary_amount" id="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', $employeeSalary->salary_amount ?: $employeeSalary->salary) }}" required min="0">
                        @error('salary_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Paid Amount</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', $employeeSalary->paid_amount) }}" required min="0">
                        @error('paid_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Remaining Amount</label>
                        <input type="number" step="0.01" id="remaining_amount_display" class="form-control bg-light" value="{{ number_format($employeeSalary->remaining_amount, 2, '.', '') }}" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $employeeSalary->payment_date?->format('Y-m-d') ?: $employeeSalary->created_at?->format('Y-m-d')) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
                            <option value="">Select Payment Method</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected(old('payment_method_id', $employeeSalary->payment_method_id) == $pm->id)>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $employeeSalary->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update Salary & Adjust Wallet</button>
                        <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const totalInput = document.getElementById('salary_amount');
                const paidInput = document.getElementById('paid_amount');
                const remainingDisplay = document.getElementById('remaining_amount_display');
                const userSelect = document.getElementById('user_id_select');
                const periodInput = document.getElementById('salary_period_input');
                const btnCalculate = document.getElementById('btn_calculate_payroll');

                function updateRemaining() {
                    const total = parseFloat(totalInput.value) || 0;
                    const paid = parseFloat(paidInput.value) || 0;
                    const remaining = Math.max(0, total - paid);
                    remainingDisplay.value = remaining.toFixed(2);
                }

                totalInput.addEventListener('input', updateRemaining);
                paidInput.addEventListener('input', updateRemaining);

                async function triggerCalculate() {
                    const userId = userSelect.value;
                    const period = periodInput.value.trim();

                    if (!userId || !period) {
                        return;
                    }

                    btnCalculate.disabled = true;
                    btnCalculate.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Calculating...';

                    try {
                        const response = await fetch("{{ route('employee-salaries.calculate') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                salary_period: period
                            })
                        });

                        const res = await response.json();
                        if (res.success && res.data) {
                            const d = res.data;
                            document.getElementById('field_monthly_salary').value = d.monthly_salary;
                            document.getElementById('field_working_days').value = d.working_days;
                            document.getElementById('field_present_days').value = d.present_days;
                            document.getElementById('field_half_days').value = d.half_days;
                            document.getElementById('field_paid_leave_days').value = d.paid_leave_days;
                            document.getElementById('field_unpaid_leave_days').value = d.unpaid_leave_days;
                            document.getElementById('field_absent_days').value = d.absent_days;
                            document.getElementById('field_per_day_salary').value = d.per_day_salary;
                            document.getElementById('field_gross_salary').value = d.gross_salary;
                            document.getElementById('field_half_day_deduction').value = d.half_day_deduction;
                            document.getElementById('field_unpaid_leave_deduction').value = d.unpaid_leave_deduction;
                            document.getElementById('field_absent_deduction').value = d.absent_deduction;
                            document.getElementById('field_attendance_deduction').value = d.attendance_deduction;
                            document.getElementById('field_net_salary').value = d.net_salary;

                            document.getElementById('badge_period_text').innerText = d.salary_period;
                            document.getElementById('disp_working_days').innerText = d.working_days;
                            document.getElementById('disp_present_days').innerText = d.present_days;
                            document.getElementById('disp_half_days').innerText = d.half_days;
                            document.getElementById('disp_paid_leave').innerText = d.paid_leave_days;
                            document.getElementById('disp_unpaid_leave').innerText = d.unpaid_leave_days;
                            document.getElementById('disp_absent_days').innerText = d.absent_days;
                            document.getElementById('disp_monthly_salary').innerText = 'Rs ' + d.monthly_salary.toFixed(2);
                            document.getElementById('disp_per_day_salary').innerText = 'Rs ' + d.per_day_salary.toFixed(2);
                            document.getElementById('disp_attendance_deduction').innerText = 'Rs ' + d.attendance_deduction.toFixed(2);
                            document.getElementById('disp_net_salary').innerText = 'Rs ' + d.net_salary.toFixed(2);

                            totalInput.value = d.net_salary.toFixed(2);
                            updateRemaining();
                        }
                    } catch (e) {
                        console.error("Payroll recalculation error:", e);
                    } finally {
                        btnCalculate.disabled = false;
                        btnCalculate.innerHTML = '<i class="ti ti-calculator me-1"></i>Recalculate Payroll';
                    }
                }

                btnCalculate.addEventListener('click', triggerCalculate);
                updateRemaining();
            });
        </script>
    @endpush
@endsection
