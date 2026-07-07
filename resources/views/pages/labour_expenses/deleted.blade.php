@extends('layouts.app')
@section('title', 'Labour Deleted History')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Labour Deleted History</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour-expenses.history') }}">Labour Expenses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Deleted History</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white border-bottom">
    <form class="row g-3 align-items-end m-0" method="GET">
        <div class="col-12 col-md-6 col-lg-4"><label class="form-label">Labour</label><select name="labour_id" class="form-select">
                <option value="">All Labour</option>@foreach($labours as $labour)<option value="{{ $labour->id }}"
                @selected(request('labour_id') == $labour->id)>{{ $labour->name }}</option>@endforeach
            </select></div>
                <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('labour-expenses.deleted-history') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
    </form>
    </div></div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover table-nowrap align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Paid date</th>
                    <th>Main Category</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Project Name</th>
                    <th>Labour Name</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Unpaid</th>
                    <th>Advanced Amount</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Payment Mode</th>
                    <th>Added By</th>
                    <th>Edited By</th>
                    <th>Advance Edited By</th>
                    <th>Deleted Date</th>
                </tr>
            </thead>
            <tbody>@forelse($transactions as $tx)<tr>
                <td>{{ $tx->id }}</td>
                <td>{{ $tx->current_date }}</td>
                <td>{{ $tx->mainCategory?->name ?? '-' }}</td>
                <td>{{ $tx->category?->name ?? '-' }}</td>
                <td>{{ $tx->description ?? '-' }}</td>
                <td>{{ $tx->project?->name ?? '-' }}</td>
                <td>{{ $tx->labour?->name ?? '-' }}</td>
                <td>{{ $tx->delete_reason ?? '-' }}</td>
                <td>Rs {{ number_format((float) $tx->amount, 2) }}</td>
                <td>Rs {{ number_format((float) $tx->paid_amount, 2) }}</td>
                <td>Rs {{ number_format((float) $tx->unpaid_amount, 2) }}</td>
                <td>Rs {{ number_format((float) $tx->extra_amount, 2) }}</td>
                <td>{{ $tx->description ?? '-' }}</td>
                <td>{{ $tx->image ?: '-' }}</td>
                <td>{{ $tx->payment_mode_label ?? '-' }}</td>
                <td>{{ $tx->user?->name ?? '-' }}</td>
                <td>{{ $tx->editedByUser?->name ?? '-' }}</td>
                <td>{{ $tx->advanceEditedByUser?->name ?? '-' }}</td>
                <td>{{ $tx->current_date }}</td>
            </tr>@empty<tr>
                    <td colspan="19" class="text-center text-muted py-4">No records</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
        </div>
        @if ($transactions->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">{{ $transactions->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
