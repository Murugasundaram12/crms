@extends('layouts.app')
@section('title', 'Vendor Advance History')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Vendor Advance History</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vendor-expenses.history') }}">Vendor Expenses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Advance History</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form class="row g-3 align-items-end m-0" method="GET">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('vendor-expenses.advance-history') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Vendor</th><th>Advance Amount</th><th>Phone</th></tr>
                    </thead>
                    <tbody>
                        @forelse($advanceVendors as $vendor)
                            <tr>
                                <td>{{ $vendor->name }}</td>
                                <td>Rs {{ number_format((float) $vendor->advance_amt, 2) }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No records</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($advanceVendors->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">{{ $advanceVendors->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
