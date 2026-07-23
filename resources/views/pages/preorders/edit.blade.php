@extends('layouts.app')

@section('title', 'Edit Preorder ' . $preorder->reference_no)

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Edit Preorder {{ $preorder->reference_no }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('preorders.index') }}">Preorders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit {{ $preorder->reference_no }}</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('preorders.show', $preorder->id) }}" class="btn btn-outline-secondary">Back to Details</a>
        </div>

        @include('partials.alerts')

        <form action="{{ route('preorders.update', $preorder->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="card-title mb-0"><i class="ti ti-box me-1"></i> Preorder Master Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Material / Tool <span class="text-danger">*</span></label>
                            <select name="tool_material_id" id="tool_material_id" class="form-select @error('tool_material_id') is-invalid @enderror" required>
                                <option value="">Select Material / Tool</option>
                                @foreach($toolsMaterials as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-rate="{{ $item->opening_rate }}" @selected((string) old('tool_material_id', $preorder->tool_material_id) === (string) $item->id)>
                                        {{ $item->name }} (SKU: {{ $item->sku ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('tool_material_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                                <option value="">Select Vendor (Optional)</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $preorder->vendor_id) === (string) $vendor->id)>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vendor_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $preorder->quantity) }}" required>
                            @error('quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $preorder->unit) }}" required>
                            @error('unit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Expected Unit Rate (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="expected_rate" id="expected_rate" class="form-control @error('expected_rate') is-invalid @enderror" value="{{ old('expected_rate', $preorder->expected_rate) }}" required>
                            @error('expected_rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Estimated Amount (Excl. GST)</label>
                            <input type="number" step="0.01" id="estimated_amount" class="form-control bg-light" readonly value="{{ $preorder->estimated_amount }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">GST % (Optional)</label>
                            <input type="number" step="0.01" min="0" max="100" name="gst_percent" id="gst_percent" class="form-control" value="{{ old('gst_percent', $preorder->gst_percent) }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Total Amount (Incl. GST)</label>
                            <input type="number" step="0.01" id="total_amount" class="form-control bg-light fw-bold" readonly value="{{ $preorder->total_amount }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Preorder Date <span class="text-danger">*</span></label>
                            <input type="date" name="preorder_date" class="form-control" value="{{ old('preorder_date', optional($preorder->preorder_date)->format('Y-m-d')) }}" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Required Date</label>
                            <input type="date" name="required_date" class="form-control" value="{{ old('required_date', optional($preorder->required_date)->format('Y-m-d')) }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', optional($preorder->expected_delivery_date)->format('Y-m-d')) }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Payment Method Master</label>
                            <select name="payment_method_id" class="form-select">
                                <option value="">Select Payment Method</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}" @selected((string) old('payment_method_id', $preorder->payment_method_id) === (string) $pm->id)>{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Workflow Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $preorder->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes / Terms</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $preorder->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('preorders.show', $preorder->id) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Update Preorder</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const expectedRateInput = document.getElementById('expected_rate');
                const quantityInput = document.getElementById('quantity');
                const gstPercentInput = document.getElementById('gst_percent');
                const estAmtInput = document.getElementById('estimated_amount');
                const totalAmtInput = document.getElementById('total_amount');

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

                quantityInput.addEventListener('input', calculateAmounts);
                expectedRateInput.addEventListener('input', calculateAmounts);
                gstPercentInput.addEventListener('input', calculateAmounts);
            });
        </script>
    @endpush
@endsection
