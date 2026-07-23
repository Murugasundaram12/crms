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
                                <option value="{{ $labour->id }}" data-salary="{{ $labour->salary }}" @selected(old('labour_id', $labourSalary->labour_id) == $labour->id)>
                                    {{ $labour->name }} {{ $phone ? '– ' . $phone : '' }}
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

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Salary / Wage Amount</label>
                        <input type="number" step="0.01" name="salary_amount" id="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', $labourSalary->salary_amount) }}" required min="0.01">
                        @error('salary_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Paid Amount</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', $labourSalary->paid_amount) }}" required min="0">
                        @error('paid_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
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
                const totalInput = document.getElementById('salary_amount');
                const paidInput = document.getElementById('paid_amount');
                const remainingDisplay = document.getElementById('remaining_amount_display');

                function calculate() {
                    const total = parseFloat(totalInput.value) || 0;
                    const paid = parseFloat(paidInput.value) || 0;
                    const remaining = Math.max(0, total - paid);
                    remainingDisplay.value = remaining.toFixed(2);
                }

                totalInput.addEventListener('input', calculate);
                paidInput.addEventListener('input', calculate);

                calculate();
            });
        </script>
    @endpush
@endsection
