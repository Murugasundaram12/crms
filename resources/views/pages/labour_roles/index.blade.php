@extends('layouts.app')

@section('title', 'Labour Roles')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Labour Roles<span class="badge badge-soft-primary ms-2">{{ $labourRoles->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Roles</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form action="{{ route('labour_roles.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search labour roles"
                        value="{{ request('q') }}">
                </div>
                <select name="salary_type" class="form-select">
                    <option value="">All Salary Types</option>
                    <option value="daily" @selected(request('salary_type') === 'daily')>Daily</option>
                    <option value="weekly" @selected(request('salary_type') === 'weekly')>Weekly</option>
                    <option value="monthly" @selected(request('salary_type') === 'monthly')>Monthly</option>
                </select>
                <button type="submit" class="btn btn-outline-light shadow">Filter</button>
            </form>
            @can('labour-roles-create')
                <a href="{{ route('labour_roles.create') }}" class="btn btn-primary">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Labour Role
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Salary Type</th>
                            <th>Salary</th>
                            <th>Labours</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($labourRoles as $labourRole)
                            <tr>
                                <td>{{ $labourRole->name }}</td>
                                <td>{{ ucfirst($labourRole->salary_type) }}</td>
                                <td>₹{{ number_format((float) $labourRole->salary, 2) }}</td>
                                <td>{{ $labourRole->labours_count }}</td>
                                <td>{{ $labourRole->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('labour_roles.edit', $labourRole->id)"
                                        editPermission="labour-roles-edit"
                                        :deleteRoute="route('labour_roles.delete', $labourRole->id)"
                                        deleteTitle="Delete Labour Role"
                                        :deleteMessage="'Are you sure you want to delete labour role \'' . $labourRole->name . '\'?'"
                                        deletePermission="labour-roles-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No labour roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $labourRoles->links() }}
            </div>
        </div>
    </div>
@endsection
