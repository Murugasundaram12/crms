@extends('layouts.app')

@section('title', 'Edit Labour Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Labour Salary</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour-salaries.index') }}">Labour Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Salary</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('labour-salaries.index') }}" class="btn btn-outline-secondary">
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
            <form action="{{ route('labour-salaries.update', $labourSalary) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label required">Labour</label>
                        <select name="labour_id" id="labour_id" class="form-select @error('labour_id') is-invalid @enderror" required>
                            @foreach ($labours as $labour)
                                @php($phone = $labour->phone ?: $labour->phone_number)
                                <option value="{{ $labour->id }}" data-salary="{{ $labour->salary }}" data-advance="{{ $labour->advance_amt }}" @selected(old('labour_id', $labourSalary->labour_id) == $labour->id)>
                                    {{ $labour->name }} {{ $phone ? '– ' . $phone : '' }} (Advance: ₹{{ number_format((float) $labour->advance_amt, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('labour_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Period Start Date</label>
                        <input type="date" name="salary_period_start" class="form-control @error('salary_period_start') is-invalid @enderror" value="{{ old('salary_period_start', $labourSalary->salary_period_start?->format('Y-m-d')) }}">
                        @error('salary_period_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Period End Date</label>
                        <input type="date" name="salary_period_end" class="form-control @error('salary_period_end') is-invalid @enderror" value="{{ old('salary_period_end', $labourSalary->salary_period_end?->format('Y-m-d')) }}">
                        @error('salary_period_end')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label required">Salary / Wage Amount</label>
                        <input type="number" step="0.01" name="salary_amount" id="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', $labourSalary->salary_amount) }}" required min="0.01">
                        @error('salary_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold mb-0" for="advance_adjusted">Advance Adjustment</label>
                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                <input type="hidden" name="advance_paid" value="0">
                                @php($isAdvanceChecked = old('advance_paid', (float) $labourSalary->advance_adjusted > 0))
                                <input class="form-check-input" type="checkbox" name="advance_paid" id="advance_paid" value="1" role="switch" @checked($isAdvanceChecked)>
                                <label class="form-check-label fw-semibold text-primary" for="advance_paid" id="advance_paid_label">
                                    <span id="advance_paid_status">{{ $isAdvanceChecked ? 'ON' : 'OFF' }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="advance_adjusted" id="advance_adjusted" class="form-control @error('advance_adjusted') is-invalid @enderror" value="{{ old('advance_adjusted', $labourSalary->advance_adjusted) }}" min="0">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap">
                            <small class="text-muted">Available: <span id="available_advance_text" class="fw-semibold text-danger">₹0.00</span></small>
                            <small id="advance_max_hint" class="text-muted d-none">Max: <span id="advance_max_text">₹0.00</span></small>
                        </div>
                        @error('advance_adjusted')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label required">Paid Amount</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', $labourSalary->paid_amount) }}" required min="0">
                        @error('paid_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Remaining Amount</label>
                        <input type="number" step="0.01" id="remaining_amount_display" class="form-control bg-light" value="{{ number_format($labourSalary->remaining_amount, 2, '.', '') }}" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $labourSalary->payment_date?->format('Y-m-d')) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
                            <option value="">Select Payment Method</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected(old('payment_method_id', $labourSalary->payment_method_id) == $pm->id)>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $labourSalary->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update Labour Salary & Adjust Wallet</button>
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
                const totalInput = document.getElementById('salary_amount');
                const advancePaidToggle = document.getElementById('advance_paid');
                const advancePaidStatus = document.getElementById('advance_paid_status');
                const advanceAdjustedInput = document.getElementById('advance_adjusted');
                const availableAdvanceText = document.getElementById('available_advance_text');
                const advanceMaxHint = document.getElementById('advance_max_hint');
                const advanceMaxText = document.getElementById('advance_max_text');
                const paidInput = document.getElementById('paid_amount');
                const remainingDisplay = document.getElementById('remaining_amount_display');

                const initialAdvanceAdjusted = parseFloat("{{ (float) $labourSalary->advance_adjusted }}") || 0;

                function getCurrentAdvanceBalance() {
                    const opt = labourSelect.options[labourSelect.selectedIndex];
                    const rawBalance = opt && opt.dataset.advance ? parseFloat(opt.dataset.advance) : 0;
                    return Math.max(0, rawBalance + initialAdvanceAdjusted);
                }

                function getSalary() {
                    return Math.max(0, parseFloat(totalInput.value) || 0);
                }

                function getMaxValidAdvanceAdjustment() {
                    return Math.min(getCurrentAdvanceBalance(), getSalary());
                }

                function syncToggleUI() {
                    const isAdvanceOn = advancePaidToggle.checked;
                    advancePaidStatus.textContent = isAdvanceOn ? 'ON' : 'OFF';

                    const balance = getCurrentAdvanceBalance();
                    availableAdvanceText.textContent = '₹' + balance.toFixed(2);

                    if (isAdvanceOn) {
                        advanceAdjustedInput.removeAttribute('readonly');
                        advanceAdjustedInput.classList.remove('bg-light');

                        const maxValid = getMaxValidAdvanceAdjustment();
                        advanceMaxText.textContent = '₹' + maxValid.toFixed(2);
                        advanceMaxHint.classList.remove('d-none');

                        const currentVal = parseFloat(advanceAdjustedInput.value) || 0;
                        if (currentVal <= 0 && maxValid > 0) {
                            advanceAdjustedInput.value = maxValid.toFixed(2);
                        } else if (currentVal > maxValid) {
                            advanceAdjustedInput.value = maxValid.toFixed(2);
                        }
                    } else {
                        advanceAdjustedInput.setAttribute('readonly', 'true');
                        advanceAdjustedInput.classList.add('bg-light');
                        advanceAdjustedInput.value = '0.00';
                        advanceMaxHint.classList.add('d-none');
                    }

                    calculate();
                }

                function calculate() {
                    const salary = getSalary();
                    const isAdvanceOn = advancePaidToggle.checked;
                    const advance = isAdvanceOn ? (parseFloat(advanceAdjustedInput.value) || 0) : 0;
                    const netPayable = Math.max(0, salary - advance);
                    const paid = parseFloat(paidInput.value) || 0;
                    const remaining = Math.max(0, netPayable - paid);
                    remainingDisplay.value = remaining.toFixed(2);
                }

                advancePaidToggle.addEventListener('change', syncToggleUI);
                labourSelect.addEventListener('change', syncToggleUI);

                totalInput.addEventListener('input', function() {
                    syncToggleUI();
                });

                advanceAdjustedInput.addEventListener('input', function() {
                    if (!advancePaidToggle.checked) {
                        this.value = '0.00';
                        return;
                    }
                    const maxValid = getMaxValidAdvanceAdjustment();
                    const entered = parseFloat(this.value) || 0;
                    if (entered > maxValid) {
                        this.value = maxValid.toFixed(2);
                    } else if (entered < 0) {
                        this.value = '0.00';
                    }
                    calculate();
                });

                paidInput.addEventListener('input', calculate);

                syncToggleUI();
            });
        </script>
    @endpush
@endsection
