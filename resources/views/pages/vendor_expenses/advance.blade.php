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
        @can('expenses-edit')
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vendorAdvanceCreditModal">
                    <i class="ti ti-plus me-1"></i>Add Amount
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#vendorAdvanceWithdrawModal">
                    <i class="ti ti-arrow-back-up me-1"></i>Withdraw
                </button>
            </div>
        @endcan
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

    @can('expenses-edit')
        <div class="modal fade" id="vendorAdvanceCreditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('vendor-expenses.advance-store') }}" class="modal-content border-0 shadow">
                    @csrf
                    <input type="hidden" name="entry_type" value="credit">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Add Vendor Advance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select" required>
                                <option value="">Select vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" min="1" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Advance paid to vendor">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-plus me-1"></i>Add Amount
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="vendorAdvanceWithdrawModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('vendor-expenses.advance-store') }}" class="modal-content border-0 shadow">
                    @csrf
                    <input type="hidden" name="entry_type" value="withdraw">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Withdraw Vendor Advance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select" required>
                                <option value="">Select vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>
                                        {{ $vendor->name }} - Rs. {{ number_format((float) $vendor->advance_amt, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" min="1" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Advance returned or corrected">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger" type="submit">
                            <i class="ti ti-arrow-back-up me-1"></i>Withdraw
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
