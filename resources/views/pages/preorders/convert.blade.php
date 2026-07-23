@extends('layouts.app')

@section('title', 'Convert Preorder ' . $preorder->reference_no)

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Convert Preorder to Purchase & Stock Receipt</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('preorders.index') }}">Preorders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Convert {{ $preorder->reference_no }}</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('preorders.show', $preorder->id) }}" class="btn btn-outline-secondary">Back to Details</a>
        </div>

        @include('partials.alerts')

        <div class="row g-4">
            <!-- Preorder Pre-filled Summary Card -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="card-title mb-0"><i class="ti ti-info-circle me-1"></i> Preorder Master Summary</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><th class="text-muted">Preorder Ref:</th><td class="fw-bold">{{ $preorder->reference_no }}</td></tr>
                            <tr><th class="text-muted">Material / Tool:</th><td class="fw-bold text-primary">{{ $preorder->toolMaterial?->name }}</td></tr>
                            <tr><th class="text-muted">Vendor:</th><td>{{ $preorder->vendor?->name ?? 'Unassigned' }}</td></tr>
                            <tr><th class="text-muted">Ordered Qty:</th><td>{{ number_format((float) $preorder->quantity, 2) }} {{ $preorder->unit }}</td></tr>
                            <tr><th class="text-muted">Delivered Qty:</th><td>{{ number_format($preorder->totalDeliveredQuantity(), 2) }} {{ $preorder->unit }}</td></tr>
                            <tr><th class="text-muted">Remaining Undelivered Qty:</th><td class="fw-bold text-danger">{{ number_format($preorder->remainingQuantity(), 2) }} {{ $preorder->unit }}</td></tr>
                            <tr><th class="text-muted">Expected Rate:</th><td>Rs. {{ number_format((float) $preorder->expected_rate, 2) }}</td></tr>
                            <tr><th class="text-muted">Estimated Total:</th><td>Rs. {{ number_format((float) $preorder->total_amount, 2) }}</td></tr>
                            <tr><th class="text-muted">Total Advance Paid:</th><td class="text-success fw-bold">Rs. {{ number_format((float) $preorder->advance_amount, 2) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Purchase Conversion Form -->
            <div class="col-12 col-lg-8">
                <form action="{{ route('preorders.convert', $preorder->id) }}" method="POST">
                    @csrf

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="card-title mb-0"><i class="ti ti-shopping-cart-plus me-1"></i> Purchase Transaction Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                    <select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $preorder->vendor_id) === (string) $vendor->id)>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Purchase / Receipt Date <span class="text-danger">*</span></label>
                                    <input type="date" name="transferred_at" class="form-control" value="{{ old('transferred_at', now()->toDateString()) }}" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Purchased / Received Quantity <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" max="{{ $preorder->remainingQuantity() }}" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $preorder->remainingQuantity()) }}" required>
                                    <small class="text-muted">Max available: {{ number_format($preorder->remainingQuantity(), 2) }} {{ $preorder->unit }}</small>
                                    @error('quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Unit Rate (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="rate" id="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate', $preorder->expected_rate) }}" required>
                                    @error('rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Total Purchase Amount (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="purchase_amount" id="purchase_amount" class="form-control @error('purchase_amount') is-invalid @enderror" value="{{ old('purchase_amount', $preorder->remainingQuantity() * $preorder->expected_rate) }}" required>
                                    @error('purchase_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Advance Paid Applied</label>
                                    <input type="number" step="0.01" class="form-control bg-light fw-bold text-success" readonly value="{{ $preorder->advance_amount }}">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Additional Payment Paid Now (Rs.)</label>
                                    <input type="number" step="0.01" min="0" name="purchase_paid_amount" class="form-control" value="{{ old('purchase_paid_amount', 0) }}">
                                    <small class="text-muted">Will be debited from your wallet if entered</small>
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

                                <div class="col-12">
                                    <label class="form-label">Notes / Remarks</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Purchase invoice number, quality check notes">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <a href="{{ route('preorders.show', $preorder->id) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-success px-4"><i class="ti ti-check me-1"></i> Confirm Purchase & Increase Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const qtyInput = document.getElementById('quantity');
                const rateInput = document.getElementById('rate');
                const amountInput = document.getElementById('purchase_amount');

                function recalc() {
                    const q = parseFloat(qtyInput.value || 0);
                    const r = parseFloat(rateInput.value || 0);
                    amountInput.value = (q * r).toFixed(2);
                }

                qtyInput.addEventListener('input', recalc);
                rateInput.addEventListener('input', recalc);
            });
        </script>
    @endpush
@endsection
