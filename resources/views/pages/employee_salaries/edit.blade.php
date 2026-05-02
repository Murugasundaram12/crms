@extends('layouts.app')

@section('title', 'Edit Employee Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Employee Salary</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-salaries.index') }}">Employee Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $employeeSalary->name }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-light shadow-sm">Back to Employee Salaries</a>
    </div>

    <form action="{{ route('employee-salaries.update', $employeeSalary) }}" method="POST" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Salary Details</h5>
                    <p class="text-muted fs-13 mb-0">Update the selected salary record.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <select name="name" class="form-select @error('name') is-invalid @enderror" required>
                                <option value="">Select Employee</option>
                                @foreach ($employeeUsers as $employeeUser)
                                    @php($roleLabel = $employeeUser->roles->pluck('name')->join(', ') ?: $employeeUser->role)
                                    <option value="{{ $employeeUser->name }}" @selected(old('name', $employeeSalary->name) === $employeeUser->name)>
                                        {{ $employeeUser->name }}{{ $roleLabel ? ' - ' . $roleLabel : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Salary <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="salary"
                                class="form-control @error('salary') is-invalid @enderror"
                                value="{{ old('salary', $employeeSalary->salary) }}" required>
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Salary Type <span class="text-danger">*</span></label>
                            @php($selectedSalaryType = old('salary_type', $employeeSalary->salary_type))
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="edit_salary_type_daily"
                                        value="daily" @checked($selectedSalaryType === 'daily') required>
                                    <label class="form-check-label" for="edit_salary_type_daily">Daily</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="edit_salary_type_weekly"
                                        value="weekly" @checked($selectedSalaryType === 'weekly') required>
                                    <label class="form-check-label" for="edit_salary_type_weekly">Weekly</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="edit_salary_type_monthly"
                                        value="monthly" @checked($selectedSalaryType === 'monthly') required>
                                    <label class="form-check-label" for="edit_salary_type_monthly">Monthly</label>
                                </div>
                            </div>
                            @error('salary_type')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="reset" class="btn btn-light">Reset</button>
                        <button type="submit" class="btn btn-primary">Update Salary</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
