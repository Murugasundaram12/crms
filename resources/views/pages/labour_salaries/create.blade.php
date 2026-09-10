@extends('layouts.app')

@section('title', 'Pay Labour Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Pay Labour Salary</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour-salaries.index') }}">Labour Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pay Labour Salary</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('labour-salaries.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>

    <!-- Payer Wallet Balance Banner -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light-subtle">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <span class="text-muted d-block fw-semibold">Current Wallet Balance:</span>
                    <h4 class="mb-0 text-success fw-bold">₹{{ number_format($payerWalletBalance, 2) }}</h4>
                </div>
                <span class="badge badge-soft-info p-2 fs-6"><i class="ti ti-wallet me-1"></i>Salary payment will be debited directly from your wallet balance.</span>
            </div>
        </div>
    </div>

    <!-- Calculation Pipeline Banner -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-around text-center flex-wrap gap-2 fw-semibold text-secondary">
                <div class="d-flex align-items-center gap-2 text-primary">
                    <i class="ti ti-calendar-check fs-4"></i> 1. Attendance Summary
                </div>
                <i class="ti ti-arrow-right text-muted d-none d-md-inline"></i>
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-calculator fs-4"></i> 2. Calculated Salary
                </div>
                <i class="ti ti-arrow-right text-muted d-none d-md-inline"></i>
                <div class="d-flex align-items-center gap-2 text-warning">
                    <i class="ti ti-minus-circle fs-4"></i> 3. Advance Adjustment
                </div>
                <i class="ti ti-arrow-right text-muted d-none d-md-inline"></i>
                <div class="d-flex align-items-center gap-2 text-success">
                    <i class="ti ti-cash fs-4"></i> 4. Net Payable
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Breakdown Summary Panel (JS Populated) -->
    <div id="attendance_summary_panel" class="card border-0 shadow-sm mb-4 bg-light d-none">
        <div class="card-body">
            <h6 class="fw-bold text-primary mb-3"><i class="ti ti-chart-bar me-1"></i> Attendance Summary</h6>
            <div class="row g-3 text-center">
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Working Days</small>
                        <strong id="summary_working_days" class="h6 mb-0 text-dark">0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Present</small>
                        <strong id="summary_present_days" class="h6 mb-0 text-success">0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Half Day</small>
                        <strong id="summary_half_days" class="h6 mb-0 text-warning">0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Absent</small>
                        <strong id="summary_absent_days" class="h6 mb-0 text-danger">0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Payable Days</small>
                        <strong id="summary_payable_days" class="h6 mb-0 text-primary">0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 bg-white rounded border">
                        <small class="text-muted d-block">Calculated Salary</small>
                        <strong id="summary_calculated_salary" class="h6 mb-0 text-success">₹0.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('labour-salaries.store') }}" method="POST">
                @csrf
                <div id="attendance_ids_container">
                    @if(old('attendance_ids'))
                        @foreach((array) old('attendance_ids') as $attId)
                            <input type="hidden" name="attendance_ids[]" value="{{ $attId }}">
                        @endforeach
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label required fw-semibold">Labour</label>
                        <select name="labour_id" id="labour_id" class="form-select @error('labour_id') is-invalid @enderror" required>
                            <option value="">Select Labour</option>
                            @foreach ($labours as $labour)
                                @php($phone = $labour->phone ?: $labour->phone_number)
                                <option value="{{ $labour->id }}" data-salary="{{ $labour->salary }}" data-advance="{{ $labour->advance_amt }}" @selected(old('labour_id', $selectedLabourId) == $labour->id)>
                                    {{ $labour->name }} {{ $phone ? '– ' . $phone : '' }} (Wage: ₹{{ number_format((float) $labour->salary, 2) }} | Advance: ₹{{ number_format((float) $labour->advance_amt, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('labour_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold">Salary Period Start</label>
                        <input type="date" name="salary_period_start" id="salary_period_start" class="form-control @error('salary_period_start') is-invalid @enderror" value="{{ old('salary_period_start', now()->startOfMonth()->toDateString()) }}">
                        @error('salary_period_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold">Salary Period End</label>
                        <input type="date" name="salary_period_end" id="salary_period_end" class="form-control @error('salary_period_end') is-invalid @enderror" value="{{ old('salary_period_end', now()->endOfMonth()->toDateString()) }}">
                        @error('salary_period_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required fw-semibold">Attendance-Based Calculated Salary</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="salary_amount" id="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', '0.00') }}" required min="0.01">
                        </div>
                        @error('salary_amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold mb-0" for="advance_adjusted">Advance Adjustment</label>
                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                <input type="hidden" name="advance_paid" value="0">
                                <input class="form-check-input" type="checkbox" name="advance_paid" id="advance_paid" value="1" role="switch" @checked(old('advance_paid', false))>
                                <label class="form-check-label fw-semibold text-primary" for="advance_paid" id="advance_paid_label">
                                    Advance Paid: <span id="advance_paid_status">{{ old('advance_paid') ? 'ON' : 'OFF' }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="advance_adjusted" id="advance_adjusted" class="form-control @error('advance_adjusted') is-invalid @enderror" value="{{ old('advance_adjusted', '0.00') }}" min="0">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap">
                            <small class="text-muted">Current Advance Balance: <span id="available_advance_text" class="fw-semibold text-danger">₹0.00</span></small>
                            <small id="advance_max_hint" class="text-muted d-none">Max: <span id="advance_max_text">₹0.00</span></small>
                        </div>
                        @error('advance_adjusted')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required fw-semibold text-success">Net Payable</label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white">₹</span>
                            <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control fw-bold fs-5 text-success @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', '0.00') }}" required min="0">
                        </div>
                        <small class="text-muted">Calculated Salary − Advance Adjustment</small>
                        @error('paid_amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required fw-semibold">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required fw-semibold">Payment Method</label>
                        <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" required>
                            <option value="">Select Payment Method</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected(old('payment_method_id') == $pm->id)>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Optional Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Add payment notes (optional)...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="ti ti-check me-1"></i> Pay Salary & Debit Wallet</button>
                        <a href="{{ route('labour-salaries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const labourSelect = document.getElementById('labour_id');
                const startDateInput = document.getElementById('salary_period_start');
                const endDateInput = document.getElementById('salary_period_end');
                const salaryAmountInput = document.getElementById('salary_amount');
                const advanceAdjustedInput = document.getElementById('advance_adjusted');
                const paidAmountInput = document.getElementById('paid_amount');
                const availableAdvanceText = document.getElementById('available_advance_text');

                const advancePaidToggle = document.getElementById('advance_paid');
                const advancePaidStatus = document.getElementById('advance_paid_status');
                const advanceMaxHint = document.getElementById('advance_max_hint');
                const advanceMaxText = document.getElementById('advance_max_text');

                const summaryPanel = document.getElementById('attendance_summary_panel');
                const summaryWorkingDays = document.getElementById('summary_working_days');
                const summaryPresentDays = document.getElementById('summary_present_days');
                const summaryHalfDays = document.getElementById('summary_half_days');
                const summaryAbsentDays = document.getElementById('summary_absent_days');
                const summaryPayableDays = document.getElementById('summary_payable_days');
                const summaryCalculatedSalary = document.getElementById('summary_calculated_salary');

                function getCurrentAdvanceBalance() {
                    const opt = labourSelect.options[labourSelect.selectedIndex];
                    return opt && opt.dataset.advance ? Math.max(0, parseFloat(opt.dataset.advance)) : 0;
                }

                function getCalculatedSalary() {
                    return Math.max(0, parseFloat(salaryAmountInput.value) || 0);
                }

                function getMaxValidAdvanceAdjustment() {
                    const balance = getCurrentAdvanceBalance();
                    const salary = getCalculatedSalary();
                    return Math.min(balance, salary);
                }

                function updateCalculations() {
                    const salary = getCalculatedSalary();
                    const isAdvanceOn = advancePaidToggle.checked;
                    const advance = isAdvanceOn ? (parseFloat(advanceAdjustedInput.value) || 0) : 0;
                    const netPayable = Math.max(0, salary - advance);

                    if (!paidAmountInput.dataset.userModified) {
                        paidAmountInput.value = netPayable.toFixed(2);
                    }
                }

                function syncToggleUI() {
                    const isAdvanceOn = advancePaidToggle.checked;
                    advancePaidStatus.textContent = isAdvanceOn ? 'ON' : 'OFF';

                    if (isAdvanceOn) {
                        advanceAdjustedInput.removeAttribute('readonly');
                        advanceAdjustedInput.classList.remove('bg-light');

                        const maxValid = getMaxValidAdvanceAdjustment();
                        advanceMaxText.textContent = '₹' + maxValid.toFixed(2);
                        advanceMaxHint.classList.remove('d-none');

                        if (!advanceAdjustedInput.dataset.userModifiedOn) {
                            advanceAdjustedInput.value = maxValid.toFixed(2);
                        } else {
                            const currentVal = parseFloat(advanceAdjustedInput.value) || 0;
                            if (currentVal > maxValid) {
                                advanceAdjustedInput.value = maxValid.toFixed(2);
                            }
                        }
                    } else {
                        advanceAdjustedInput.setAttribute('readonly', 'true');
                        advanceAdjustedInput.classList.add('bg-light');
                        advanceAdjustedInput.value = '0.00';
                        advanceAdjustedInput.removeAttribute('data-user-modified-on');
                        advanceMaxHint.classList.add('d-none');
                    }

                    updateCalculations();
                }

                function fetchAttendanceSummary() {
                    const labourId = labourSelect.value;
                    const startDate = startDateInput.value;
                    const endDate = endDateInput.value;

                    const currentAdvance = getCurrentAdvanceBalance();
                    availableAdvanceText.textContent = '₹' + currentAdvance.toFixed(2);

                    if (!labourId || !startDate || !endDate) {
                        summaryPanel.classList.add('d-none');
                        syncToggleUI();
                        return;
                    }

                    const url = `{{ route('labour-salaries.calculate') }}?labour_id=${labourId}&salary_period_start=${startDate}&salary_period_end=${endDate}`;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.calculated_salary !== undefined) {
                                summaryWorkingDays.textContent = data.total_working_days;
                                summaryPresentDays.textContent = data.present_days;
                                summaryHalfDays.textContent = data.half_days;
                                summaryAbsentDays.textContent = data.absent_days;
                                summaryPayableDays.textContent = data.payable_days;
                                summaryCalculatedSalary.textContent = '₹' + data.calculated_salary.toFixed(2);
                                summaryPanel.classList.remove('d-none');

                                const attContainer = document.getElementById('attendance_ids_container');
                                if (attContainer) {
                                    attContainer.innerHTML = '';
                                    if (Array.isArray(data.attendance_ids)) {
                                        data.attendance_ids.forEach(function(id) {
                                            const input = document.createElement('input');
                                            input.type = 'hidden';
                                            input.name = 'attendance_ids[]';
                                            input.value = id;
                                            attContainer.appendChild(input);
                                        });
                                    }
                                }

                                if (!salaryAmountInput.dataset.userModified) {
                                    salaryAmountInput.value = data.calculated_salary.toFixed(2);
                                }

                                syncToggleUI();
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching attendance summary:', err);
                            syncToggleUI();
                        });
                }

                advancePaidToggle.addEventListener('change', function() {
                    advanceAdjustedInput.removeAttribute('data-user-modified-on');
                    paidAmountInput.removeAttribute('data-user-modified');
                    syncToggleUI();
                });

                labourSelect.addEventListener('change', function() {
                    salaryAmountInput.removeAttribute('data-user-modified');
                    advanceAdjustedInput.removeAttribute('data-user-modified-on');
                    paidAmountInput.removeAttribute('data-user-modified');
                    fetchAttendanceSummary();
                });

                startDateInput.addEventListener('change', fetchAttendanceSummary);
                endDateInput.addEventListener('change', fetchAttendanceSummary);

                salaryAmountInput.addEventListener('input', function() {
                    this.dataset.userModified = 'true';
                    syncToggleUI();
                });

                advanceAdjustedInput.addEventListener('input', function() {
                    if (!advancePaidToggle.checked) {
                        this.value = '0.00';
                        return;
                    }
                    this.dataset.userModifiedOn = 'true';
                    const maxValid = getMaxValidAdvanceAdjustment();
                    const entered = parseFloat(this.value) || 0;
                    if (entered > maxValid) {
                        this.value = maxValid.toFixed(2);
                    } else if (entered < 0) {
                        this.value = '0.00';
                    }
                    updateCalculations();
                });

                paidAmountInput.addEventListener('input', function() {
                    this.dataset.userModified = 'true';
                });

                const form = advancePaidToggle.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        if (!advancePaidToggle.checked) {
                            advanceAdjustedInput.value = '0.00';
                        }
                    });
                }

                syncToggleUI();
                fetchAttendanceSummary();
            });
        </script>
    @endpush
@endsection
