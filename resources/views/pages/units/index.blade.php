@extends('layouts.app')

@section('title', 'Units')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Units<span class="badge badge-soft-primary ms-2">{{ $units->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Units</li>
                </ol>
            </nav>
        </div>
        @can('units-create')
            <a href="{{ route('units.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Unit
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('units.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-5">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search unit name or code" value="{{ request('q') }}">
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
                    <a href="{{ route('units.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Unit Name</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                            <tr>
                                <td>{{ $unit->name }}</td>
                                <td><span class="badge badge-soft-primary">{{ $unit->code }}</span></td>
                                <td>{{ $unit->description ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $unit->active_status ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                        {{ $unit->active_status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('units.edit', $unit)"
                                        editPermission="units-edit"
                                        :deleteRoute="route('units.destroy', $unit)"
                                        deleteTitle="Delete Unit"
                                        :deleteMessage="'Are you sure you want to delete unit \'' . $unit->display_name . '\'?'"
                                        deletePermission="units-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No units found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($units->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $units->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
