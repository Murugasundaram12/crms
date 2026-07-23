@extends('layouts.app')

@section('title', 'Add Employee Salary Payment')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Add Employee Salary Payment</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-salaries.index') }}">Employee Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Salary Payment</li>
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
                <span class="badge badge-soft-info p-2"><i class="ti ti-info-circle me-1"></i>Salary payments will be debited directly from your wallet balance.</span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('employee-salaries.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label required">Employee</label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select Employee</option>
                            @foreach ($employeeUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Salary Period / Month</label>
                        <input type="text" name="salary_period" class="form-control @error('salary_period') is-invalid @enderror" value="{{ old('salary_period', now()->format('F Y')) }}" placeholder="e.g. July 2026" required>
                        @error('salary_period')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Salary Amount</label>
                        <input type="number" step="0.01" name="salary_amount" id="salary_amount" class="form-control @error('salary_amount') is-invalid @enderror" value="{{ old('salary_amount', 0) }}" required min="0.01">
                        @error('salary_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label required">Paid Amount (Debits from your wallet)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', 0) }}" required min="0">
                        @error('paid_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Remaining Amount</label>
                        <input type="number" step="0.01" id="remaining_amount_display" class="form-control bg-light" value="0.00" readonly>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label required">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
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
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Add payment notes...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">Save Salary & Debit Wallet</button>
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
