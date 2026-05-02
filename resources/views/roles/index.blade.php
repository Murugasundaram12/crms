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
                    <div class="table-responsive custom-table">
                        <table class="table table-nowrap datatable" id="roles-table">
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
                                                <button class="btn btn-sm btn-icon dropdown-toggle" data-bs-toggle="dropdown">
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
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#roles-table').DataTable({
                pageLength: 10,
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: 4 }
                ]
            });

        });
    </script>
@endpush
