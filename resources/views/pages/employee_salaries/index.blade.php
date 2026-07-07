@extends('layouts.app')

@section('title', 'Employee Salaries')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Employee Salaries<span class="badge badge-soft-primary ms-2">{{ $employeeSalaries->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Salaries</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('employees-salary-create')
                <a href="{{ route('employee-salaries.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Salary
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('employee-salaries.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search salaries" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Salary Type</label>
                    <select name="salary_type" class="form-select">
                        <option value="">All Salary Types</option>
                        <option value="daily" @selected(request('salary_type') === 'daily')>Daily</option>
                        <option value="weekly" @selected(request('salary_type') === 'weekly')>Weekly</option>
                        <option value="monthly" @selected(request('salary_type') === 'monthly')>Monthly</option>
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
                    <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Name</th>
                            <th>Salary</th>
                            <th>Salary Type</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeSalaries as $employeeSalary)
                            <tr>
                                <td>{{ $employeeSalary->name }}</td>
                                <td>Rs {{ number_format((float) $employeeSalary->salary, 2) }}</td>
                                <td>{{ ucfirst($employeeSalary->salary_type) }}</td>
                                <td>{{ $employeeSalary->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('employee-salaries.edit', $employeeSalary)"
                                        editPermission="employees-salary-edit"
                                        :deleteRoute="route('employee-salaries.destroy', $employeeSalary)"
                                        deleteTitle="Delete Employee Salary"
                                        :deleteMessage="'Are you sure you want to delete salary \'' . $employeeSalary->name . '\'?'"
                                        deletePermission="employees-salary-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No employee salaries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($employeeSalaries->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $employeeSalaries->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
