@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Vendors<span class="badge badge-soft-primary ms-2">{{ $vendors->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Vendors</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('vendors-create')
                <a href="{{ route('vendors.create') }}" class="btn btn-primary">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Vendor
                </a>
            @endcan
        </div>
    </div>

    <div class="card border rounded-0 mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('vendors.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search vendors" value="{{ request('q') }}">
                    </div>
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
                    <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Advance Amount</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vendors as $vendor)
                            <tr>
                                <td>{{ $vendor->name }}</td>
                                <td>{{ Str::limit($vendor->address, 30) }}</td>
                                <td>{{ $vendor->phone ?: '-' }}</td>
                                <td>₹{{ number_format((float) $vendor->advance_amount, 2) }}</td>
                                <td>{{ $vendor->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('vendors.edit', $vendor->id)"
                                        editPermission="vendors-edit"
                                        :deleteRoute="route('vendors.destroy', $vendor->id)"
                                        deleteTitle="Delete Vendor"
                                        :deleteMessage="'Are you sure you want to delete vendor \'' . $vendor->name . '\'?'"
                                        deletePermission="vendors-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vendors->links() }}
            </div>
        </div>
    </div>
@endsection
