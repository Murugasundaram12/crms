@extends('layouts.app')

@section('title', 'Labour Salaries')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Labour Salaries<span class="badge badge-soft-primary ms-2">{{ $labourSalaries->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Salaries</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('labour-salaries-create')
                <a href="{{ route('labour-salaries.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-cash me-1"></i>Pay Labour Salary
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('labour-salaries.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-5">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search by labour name, phone or notes" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('labour-salaries.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Labour Name</th>
                            <th>Phone</th>
                            <th>Wage / Salary</th>
                            <th>Paid Amount</th>
                            <th>Remaining</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                            <th>Paid By</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($labourSalaries as $ls)
                            <tr>
                                <td><span class="fw-semibold text-dark">{{ $ls->labour?->name }}</span></td>
                                <td>{{ $ls->labour?->phone ?: ($ls->labour?->phone_number ?: '-') }}</td>
                                <td>Rs {{ number_format((float) $ls->salary_amount, 2) }}</td>
                                <td><span class="badge badge-soft-success">Rs {{ number_format((float) $ls->paid_amount, 2) }}</span></td>
                                <td class="fw-bold text-danger">Rs {{ number_format((float) $ls->remaining_amount, 2) }}</td>
                                <td>{{ $ls->paymentMethod?->name ?: 'N/A' }}</td>
                                <td>{{ $ls->payment_date?->format('d M Y') }}</td>
                                <td>{{ $ls->payer?->name ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ match($ls->status) {
                                        'paid' => 'badge-soft-success',
                                        'partial' => 'badge-soft-warning',
                                        default => 'badge-soft-danger'
                                    } }}">
                                        {{ ucfirst($ls->status ?: 'paid') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('labour-salaries.edit', $ls)"
                                        editPermission="labour-salaries-edit"
                                        :deleteRoute="route('labour-salaries.destroy', $ls)"
                                        deleteTitle="Delete Labour Salary"
                                        :deleteMessage="'Are you sure you want to delete labour salary record for \'' . ($ls->labour?->name ?: 'this labour') . '\'?'"
                                        deletePermission="labour-salaries-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No labour salary records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($labourSalaries->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $labourSalaries->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
