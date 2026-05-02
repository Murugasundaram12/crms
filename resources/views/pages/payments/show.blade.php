@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Payment Details</h4>
        <a href="{{ route('payments.index') }}" class="btn btn-light">Back</a>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Quotation Number</label>
                    <div>{{ $payment->quotation?->quotation_number ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Invoice Number</label>
                    <div>{{ $payment->invoice_number ?? $payment->payment_code ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Client</label>
                    <div>{{ $payment->client?->name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Project</label>
                    <div>{{ $payment->project?->name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Stage</label>
                    <div>{{ $payment->stage?->name ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Amount</label>
                    <div>{{ number_format((float) $payment->amount, 2) }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Method</label>
                    <div>{{ ucfirst($payment->method ?? '-') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Status</label>
                    <div>{{ ucfirst($payment->status ?? '-') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Due Date</label>
                    <div>{{ optional($payment->due_date)->format('d M Y') ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Paid At</label>
                    <div>{{ optional($payment->payment_date)->format('d M Y h:i A') ?? '-' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted fw-bold">Transaction ID</label>
                    <div>{{ $payment->transaction_id ?? '-' }}</div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted fw-bold">Notes</label>
                    <div>{{ $payment->notes ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
