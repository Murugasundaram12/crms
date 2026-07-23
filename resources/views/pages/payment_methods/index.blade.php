@extends('layouts.app')

@section('title', 'Payment Method Master')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Payment Method Master <span class="badge badge-soft-primary ms-2">{{ $paymentMethods->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment Methods</li>
                </ol>
            </nav>
        </div>
        @if(auth()->user()->hasPermission('payment-methods-create'))
            <a href="{{ route('payment-methods.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Payment Method
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('payment-methods.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-5">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search by name, code or type" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>#</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentMethods as $index => $pm)
                            <tr>
                                <td>{{ $paymentMethods->firstItem() + $index }}</td>
                                <td><span class="fw-semibold text-dark">{{ $pm->name }}</span></td>
                                <td><span class="badge badge-soft-info">{{ $pm->code }}</span></td>
                                <td>{{ $pm->type ?: 'General' }}</td>
                                <td>{{ $pm->sort_order }}</td>
                                <td>
                                    <form action="{{ route('payment-methods.toggle', $pm) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="border-0 bg-transparent p-0" title="Click to toggle status">
                                            <span class="badge {{ $pm->active_status ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                                {{ $pm->active_status ? 'Active' : 'Inactive' }}
                                            </span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        @if(auth()->user()->hasPermission('payment-methods-edit'))
                                            <a href="{{ route('payment-methods.edit', $pm) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()->hasPermission('payment-methods-delete'))
                                            <form action="{{ route('payment-methods.destroy', $pm) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this payment method?');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No payment methods found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($paymentMethods->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $paymentMethods->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
