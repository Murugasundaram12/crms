@extends('layouts.app')

@section('title', 'Permissions Management')

@section('content')
    <!-- Page Header -->
    <div class="mb-4">
        <h4 class="mb-1">Permissions Management</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Permissions</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Permissions List</h5>
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary">Add Permission</a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('permissions.index') }}" class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Search</label>
                            <div class="input-icon input-icon-start position-relative">
                                <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="Search permissions" value="{{ request('q') }}">
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
                            <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                        </div>
                    </form>
                    <div class="table-responsive custom-table">
                        <table class="table table-nowrap mb-0" id="permissions-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Key</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->id }}</td>
                                        <td>{{ $permission->name }}</td>
                                        <td><code>{{ $permission->key }}</code></td>
                                        <td>{{ optional($permission->created_at)->format('d M Y') ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a href="{{ route('permissions.edit', $permission) }}"
                                                        class="dropdown-item">
                                                        <i class="ti ti-edit text-primary me-1"></i>Edit
                                                    </a>
                                                    <button type="button" class="dropdown-item text-danger crm-delete-trigger"
                                                        data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                        data-delete-action="{{ route('permissions.destroy', $permission) }}"
                                                        data-delete-title="Delete Permission"
                                                        data-delete-message="Are you sure you want to delete permission '{{ $permission->name }}'?">
                                                        <i class="ti ti-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No permissions found. <a href="{{ route('permissions.create') }}">Create one</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($permissions->hasPages())
                        <div class="d-flex justify-content-end mt-3">
                            {{ $permissions->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
