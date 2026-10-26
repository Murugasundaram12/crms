@extends('layouts.app')

@section('title', 'Create Preorder')

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Create Preorder</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('preorders.index') }}">Preorders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('preorders.index') }}" class="btn btn-outline-secondary">Back to Preorders</a>
        </div>

        @include('partials.alerts')

        <form action="{{ route('preorders.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="card-title mb-0"><i class="ti ti-box me-1"></i> Preorder Master Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Material / Tool <span class="text-danger">*</span></label>
                            <select name="tool_material_id" id="tool_material_id" class="form-select @error('tool_material_id') is-invalid @enderror"
                                @error('tool_material_id') aria-describedby="tool_material_id_error" aria-invalid="true" @enderror required>
                                <option value="">Select Material / Tool</option>
                                @foreach($toolsMaterials as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-rate="{{ $item->opening_rate }}" @selected((string) old('tool_material_id') === (string) $item->id)>
                                        {{ $item->name }} (SKU: {{ $item->sku ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('tool_material_id')
                                <div class="invalid-feedback d-block" id="tool_material_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror"
                                @error('vendor_id') aria-describedby="vendor_id_error" aria-invalid="true" @enderror>
                                <option value="">Select Vendor (Optional)</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vendor_id')
                                <div class="invalid-feedback d-block" id="vendor_id_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}"
                                @error('quantity') aria-describedby="quantity_error" aria-invalid="true" @enderror required>
                            @error('quantity')
                                <div class="invalid-feedback d-block" id="quantity_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror"
                                @error('unit') aria-describedby="unit_error" aria-invalid="true" @enderror required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->code }}" @selected((string) old('unit', 'pcs') === (string) $unit->code)>
                                        {{ $unit->name }} ({{ $unit->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('unit')
                                <div class="invalid-feedback d-block" id="unit_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Expected Unit Rate (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="expected_rate" id="expected_rate" class="form-control @error('expected_rate') is-invalid @enderror" value="{{ old('expected_rate', 0) }}"
                                @error('expected_rate') aria-describedby="expected_rate_error" aria-invalid="true" @enderror required>
                            @error('expected_rate')
                                <div class="invalid-feedback d-block" id="expected_rate_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Estimated Amount (Excl. GST)</label>
                            <input type="number" step="0.01" id="estimated_amount" class="form-control bg-light" readonly value="0.00">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">GST % (Optional)</label>
                            <input type="number" step="0.01" min="0" max="100" name="gst_percent" id="gst_percent" class="form-control @error('gst_percent') is-invalid @enderror" value="{{ old('gst_percent', 0) }}"
                                @error('gst_percent') aria-describedby="gst_percent_error" aria-invalid="true" @enderror>
                            @error('gst_percent')
                                <div class="invalid-feedback d-block" id="gst_percent_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Total Amount (Incl. GST)</label>
                            <input type="number" step="0.01" id="total_amount" class="form-control bg-light fw-bold" readonly value="0.00">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Preorder Date <span class="text-danger">*</span></label>
                            <input type="date" name="preorder_date" id="preorder_date" class="form-control @error('preorder_date') is-invalid @enderror" value="{{ old('preorder_date', now()->toDateString()) }}"
                                @error('preorder_date') aria-describedby="preorder_date_error" aria-invalid="true" @enderror required>
                            @error('preorder_date')
                                <div class="invalid-feedback d-block" id="preorder_date_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Required Date</label>
                            <input type="date" name="required_date" id="required_date" class="form-control @error('required_date') is-invalid @enderror" value="{{ old('required_date') }}"
                                @error('required_date') aria-describedby="required_date_error" aria-invalid="true" @enderror>
                            @error('required_date')
                                <div class="invalid-feedback d-block" id="required_date_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control @error('expected_delivery_date') is-invalid @enderror" value="{{ old('expected_delivery_date') }}"
                                @error('expected_delivery_date') aria-describedby="expected_delivery_date_error" aria-invalid="true" @enderror>
                            @error('expected_delivery_date')
                                <div class="invalid-feedback d-block" id="expected_delivery_date_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes / Terms</label>
                            <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Special requirements, vendor delivery conditions"
                                @error('notes') aria-describedby="notes_error" aria-invalid="true" @enderror>{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block" id="notes_error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Initial Advance Amount (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="advance_amount" id="advance_amount" class="form-control @error('advance_amount') is-invalid @enderror" value="{{ old('advance_amount', 0) }}"
                                @error('advance_amount') aria-describedby="advance_amount_error" aria-invalid="true" @enderror>
                            @error('advance_amount')
                                <div class="invalid-feedback d-block" id="advance_amount_error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Payment Method Master <span class="text-muted small">(required for advance)</span></label>
                            <select name="payment_method_id" id="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror"
                                @error('payment_method_id') aria-describedby="payment_method_id_error" aria-invalid="true" @enderror>
                                <option value="">Select Payment Method</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}" @selected((string) old('payment_method_id') === (string) $pm->id)>{{ $pm->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')
                                <div class="invalid-feedback d-block" id="payment_method_id_error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('preorders.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Create Preorder</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const materialSelect = document.getElementById('tool_material_id');
                const unitSelect = document.getElementById('unit');
                const expectedRateInput = document.getElementById('expected_rate');
                const quantityInput = document.getElementById('quantity');
                const gstPercentInput = document.getElementById('gst_percent');

                const estAmtInput = document.getElementById('estimated_amount');
                const totalAmtInput = document.getElementById('total_amount');

                function selectUnit(unit) {
                    if (!unit) return;
                    const exists = Array.from(unitSelect.options).some(option => option.value === unit);
                    if (exists) unitSelect.value = unit;
                }

                function calculateAmounts() {
                    const qty = parseFloat(quantityInput.value || 0);
                    const rate = parseFloat(expectedRateInput.value || 0);
                    const gst = parseFloat(gstPercentInput.value || 0);

                    const est = qty * rate;
                    const gstAmt = est * (gst / 100);
                    const total = est + gstAmt;

                    estAmtInput.value = est.toFixed(2);
                    totalAmtInput.value = total.toFixed(2);
                }

                materialSelect.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    selectUnit(opt.dataset.unit);
                    if (opt.dataset.rate && parseFloat(opt.dataset.rate) > 0) {
                        expectedRateInput.value = opt.dataset.rate;
                    }
                    calculateAmounts();
                });

                quantityInput.addEventListener('input', calculateAmounts);
                expectedRateInput.addEventListener('input', calculateAmounts);
                gstPercentInput.addEventListener('input', calculateAmounts);

                calculateAmounts();
            });
        </script>
    @endpush
@endsection
