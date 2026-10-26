@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h4 class="m-0">Add Transfer</h4>
            <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between mb-3">
            <span class="fw-semibold"><i class="ti ti-wallet me-1"></i> Available Wallet Balance:</span>
            <span class="fs-5 fw-bold text-primary">Rs {{ number_format((float) (Auth::user()->wallet ?? 0), 2) }}</span>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('transfers.store') }}" novalidate>
            @csrf

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Transfer Type</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="transfer_type" value="employee" id="transfer_type_employee" {{ old('transfer_type') === 'employee' ? 'checked' : '' }} @error('transfer_type') aria-describedby="transfer_type_error" aria-invalid="true" @enderror />
                                    Employee
                                </label>
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="transfer_type" value="vendor" id="transfer_type_vendor" {{ old('transfer_type') === 'vendor' ? 'checked' : '' }} @error('transfer_type') aria-describedby="transfer_type_error" aria-invalid="true" @enderror />
                                    Vendor
                                </label>
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="transfer_type" value="labour" id="transfer_type_labour" {{ old('transfer_type') === 'labour' ? 'checked' : '' }} @error('transfer_type') aria-describedby="transfer_type_error" aria-invalid="true" @enderror />
                                    Labour
                                </label>
                            </div>
                            @error('transfer_type')
                                <div class="invalid-feedback d-block" id="transfer_type_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="employee_id_field" style="display:none;">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror" @error('employee_id') aria-describedby="employee_id_error" aria-invalid="true" @enderror>
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ (string) old('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                        {{ $employee->name ?? $employee->full_name ?? 'Employee #' . $employee->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback d-block" id="employee_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="vendor_id_field" style="display:none;">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select form-select-sm @error('vendor_id') is-invalid @enderror" @error('vendor_id') aria-describedby="vendor_id_error" aria-invalid="true" @enderror>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ (string) old('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name ?? 'Vendor #' . $vendor->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vendor_id')
                                <div class="invalid-feedback d-block" id="vendor_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="labour_id_field" style="display:none;">
                            <label class="form-label">Labour</label>
                            <select name="labour_id" id="labour_id" class="form-select form-select-sm @error('labour_id') is-invalid @enderror" @error('labour_id') aria-describedby="labour_id_error" aria-invalid="true" @enderror>
                                <option value="">Select Labour</option>
                                @foreach($labours as $labour)
                                    <option value="{{ $labour->id }}" {{ (string) old('labour_id') === (string) $labour->id ? 'selected' : '' }}>
                                        {{ $labour->name ?? 'Labour #' . $labour->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('labour_id')
                                <div class="invalid-feedback d-block" id="labour_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror"
                                value="{{ old('amount') }}" @error('amount') aria-describedby="amount_error" aria-invalid="true" @enderror />
                            @error('amount')
                                <div class="invalid-feedback d-block" id="amount_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method_id" id="payment_method_id" class="form-select form-select-sm @error('payment_method_id') is-invalid @enderror" @error('payment_method_id') aria-describedby="payment_method_id_error" aria-invalid="true" @enderror required>
                                <option value="">Select Payment Method</option>
                                @foreach($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}" {{ (string) old('payment_method_id') === (string) $paymentMethod->id ? 'selected' : '' }}>
                                        {{ $paymentMethod->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method_id')
                                <div class="invalid-feedback d-block" id="payment_method_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Current Date</label>
                            <input type="date" name="current_date" id="current_date" class="form-control form-control-sm @error('current_date') is-invalid @enderror"
                                value="{{ old('current_date', now()->format('Y-m-d')) }}" @error('current_date') aria-describedby="current_date_error" aria-invalid="true" @enderror />
                            @error('current_date')
                                <div class="invalid-feedback d-block" id="current_date_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Current Time</label>
                            <input type="text" name="current_time" id="current_time" class="form-control form-control-sm @error('current_time') is-invalid @enderror"
                                value="{{ old('current_time', now()->format('h:i:s A')) }}" placeholder="HH:MM:SS AM/PM" @error('current_time') aria-describedby="current_time_error" aria-invalid="true" @enderror />
                            @error('current_time')
                                <div class="invalid-feedback d-block" id="current_time_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" @error('description') aria-describedby="description_error" aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block" id="description_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function syncTransferTypeFields() {
            const employeeField = document.getElementById('employee_id_field');
            const vendorField = document.getElementById('vendor_id_field');
            const labourField = document.getElementById('labour_id_field');
            const employeeRadio = document.getElementById('transfer_type_employee');
            const vendorRadio = document.getElementById('transfer_type_vendor');
            const labourRadio = document.getElementById('transfer_type_labour');

            if (employeeRadio && employeeRadio.checked) {
                employeeField.style.display = '';
                vendorField.style.display = 'none';
                labourField.style.display = 'none';
            } else if (vendorRadio && vendorRadio.checked) {
                vendorField.style.display = '';
                employeeField.style.display = 'none';
                labourField.style.display = 'none';
            } else if (labourRadio && labourRadio.checked) {
                labourField.style.display = '';
                employeeField.style.display = 'none';
                vendorField.style.display = 'none';
            } else {
                employeeField.style.display = 'none';
                vendorField.style.display = 'none';
                labourField.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const employeeRadio = document.getElementById('transfer_type_employee');
            const vendorRadio = document.getElementById('transfer_type_vendor');
            const labourRadio = document.getElementById('transfer_type_labour');
            if (employeeRadio) employeeRadio.addEventListener('change', syncTransferTypeFields);
            if (vendorRadio) vendorRadio.addEventListener('change', syncTransferTypeFields);
            if (labourRadio) labourRadio.addEventListener('change', syncTransferTypeFields);
            syncTransferTypeFields();

            const amountInput = document.querySelector('input[name="amount"]');
            const userWallet = {{ (float) (Auth::user()->wallet ?? 0) }};
            if (amountInput) {
                amountInput.addEventListener('input', function () {
                    const val = parseFloat(this.value) || 0;
                    let warn = document.getElementById('amount_wallet_warning');
                    if (val > userWallet) {
                        if (!warn) {
                            warn = document.createElement('div');
                            warn.id = 'amount_wallet_warning';
                            warn.className = 'text-danger small mt-1';
                            this.parentNode.appendChild(warn);
                        }
                        warn.textContent = 'Warning: Entered amount exceeds your available wallet balance (Rs ' + userWallet.toFixed(2) + ').';
                    } else if (warn) {
                        warn.remove();
                    }
                });
            }
        });
    </script>
@endsection