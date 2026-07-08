@extends('layouts.app')

@section('title', 'Roles Management')

@section('content')
    <!-- Page Header -->
    <div class="mb-4">
        <h4 class="mb-1">Roles Management</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Roles</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Roles List</h5>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">Add Role</a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('roles.index') }}" class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Search</label>
                            <div class="input-icon input-icon-start position-relative">
                                <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="Search roles" value="{{ request('q') }}">
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
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                        </div>
                    </form>
                    <div class="table-responsive custom-table">
                        <table class="table table-nowrap mb-0" id="roles-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td>{{ $role->id }}</td>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->description ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ $role->permissions_count }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a href="{{ route('roles.edit', $role) }}" class="dropdown-item">
                                                        <i class="ti ti-edit text-primary me-1"></i>Edit
                                                    </a>

                                                    <button type="button" class="dropdown-item text-danger crm-delete-trigger"
                                                        data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                        data-delete-action="{{ route('roles.destroy', $role) }}"
                                                        data-delete-title="Delete Role"
                                                        data-delete-message="Are you sure you want to delete role '{{ $role->name }}'? This will also remove its permission assignments.">
                                                        <i class="ti ti-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No roles found. <a href="{{ route('roles.create') }}">Create one</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($roles->hasPages())
                        <div class="d-flex justify-content-end mt-3">
                            {{ $roles->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
