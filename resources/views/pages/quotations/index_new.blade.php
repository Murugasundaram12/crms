@extends('layouts.app')

@section('title', 'Quotations')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Quotations<span
                    class="badge bg-soft-primary text-primary ms-2">{{ $quotations->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Quotations</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('quotations-create')
                <a href="{{ route('quotations.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Quotation
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm quotation-list-card">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <form method="GET" class="row g-3 align-items-end flex-grow-1 m-0">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search client or notes"
                            value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('quotations.list') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 quotation-mini-stats">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-soft-primary text-primary">Total {{ $quotations->total() }}</span>
                    <span class="badge bg-soft-warning text-warning">Draft
                        {{ $quotations->where('status', 'draft')->count() }}</span>
                    <span class="badge bg-soft-success text-success">Approved
                        {{ $quotations->where('status', 'approved')->count() }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle quotation-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Quotation No</th>
                            <th>Client</th>
                            <th>Create Date</th>
                            <th>Title</th>
                            <th>Total Amount</th>
                            {{-- <th>Total</th> --}}
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotations as $quotation)
                            <tr>
                                <td><span class="fw-semibold">#{{ $quotation->id }}</span></td>
                                <td>
                                    <a href="{{ route('quotations.show', $quotation->id) }}" class="fw-semibold">
                                        {{ $quotation->quotation_number ?? '-' }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-md bg-soft-primary text-primary">
                                            {{ strtoupper(substr($quotation->client->name ?? 'N/A', 0, 1)) }}
                                        </span>
                                        <div>
                                            <h6 class="mb-1">{{ $quotation->client->name ?? 'N/A' }}</h6>
                                            <span class="text-muted fs-13">Customer quotation</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quotation->quotation_date?->format('d M Y') ?? '-' }}</td>
                                <td class="fw-semibold">{{ Str::limit($quotation->quotation_title ?? '-', 40) }}</td>
                                <td>Rs {{ number_format($quotation->amount ?? 0, 2) }}</td>
                                {{-- <td><span class="fw-semibold text-dark">₹{{ number_format((float) $quotation->total_amount,
                                        2) }}</span>
                                </td> --}}
                                <td><span
                                        class="badge {{ $quotation->status === 'approved' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }}">{{ ucfirst($quotation->status) }}</span>
                                </td>
                                <td>{{ $quotation->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('quotations.edit', $quotation->id)"
                                        editPermission="quotations-edit"
                                        :deleteRoute="route('quotations.delete', $quotation->id)"
                                        deleteTitle="Delete Quotation"
                                        :deleteMessage="'Are you sure you want to delete quotation ' . ($quotation->quotation_number ?? '#' . $quotation->id) . '?'"
                                        deletePermission="quotations-delete"
                                    >
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('quotations.show', $quotation->id) }}" title="View Details">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('quotations.stream', $quotation->id) }}" target="_blank" title="View / Print PDF">
                                            <i class="ti ti-printer me-1"></i>Print
                                        </a>
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('quotations.download', $quotation->id) }}" title="Download PDF">
                                            <i class="ti ti-file-download me-1"></i>PDF
                                        </a>
                                    </x-action-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="quotation-empty-state text-center py-5">
                                        <span class="avatar avatar-xl bg-soft-primary text-primary mb-3">
                                            <i class="ti ti-file-invoice fs-28"></i>
                                        </span>
                                        <h5 class="mb-2">No quotations found</h5>
                                        <p class="text-muted mb-3">Get started by creating your first quotation.</p>
                                        @can('quotations-create')
                                            <a href="{{ route('quotations.create') }}" class="btn btn-primary">Create Quotation</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($quotations->hasPages())
                <div class="d-flex justify-content-end mt-4">
                    {{ $quotations->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .quotation-list-card {
            border-color: rgba(226, 232, 240, 0.7);
            background-color: #fff;
        }

        .quotation-list-card .card-header {
            padding: 1rem 1.25rem;
        }

        .quotation-mini-stats {
            padding: 16px 18px;
            border-radius: 16px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .quotation-table thead th {
            color: #4b5563;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e5e7eb;
        }

        .quotation-table tbody tr td {
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .quotation-empty-state {
            max-width: 420px;
            margin: 0 auto;
        }
    </style>
@endpush
