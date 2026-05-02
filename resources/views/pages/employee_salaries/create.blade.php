@extends('layouts.app')

@section('title', 'Create Employee Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Create Employee Salary</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employee-salaries.index') }}">Employee Salaries</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-light shadow-sm">Back to Employee Salaries</a>
    </div>

    <form action="{{ route('employee-salaries.store') }}" method="POST" class="row g-4">
        @csrf

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Salary Details</h5>
                    <p class="text-muted fs-13 mb-0">Add a salary record with a required name, amount, and salary type.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <select name="name" class="form-select @error('name') is-invalid @enderror" required>
                                <option value="">Select Employee</option>
                                @foreach ($employeeUsers as $employeeUser)
                                    @php($roleLabel = $employeeUser->roles->pluck('name')->join(', ') ?: $employeeUser->role)
                                    <option value="{{ $employeeUser->name }}" @selected(old('name') === $employeeUser->name)>
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
                                class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary') }}" required>
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label d-block">Salary Type <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="salary_type_daily"
                                        value="daily" @checked(old('salary_type') === 'daily') required>
                                    <label class="form-check-label" for="salary_type_daily">Daily</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="salary_type_weekly"
                                        value="weekly" @checked(old('salary_type') === 'weekly') required>
                                    <label class="form-check-label" for="salary_type_weekly">Weekly</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="salary_type" id="salary_type_monthly"
                                        value="monthly" @checked(old('salary_type') === 'monthly') required>
                                    <label class="form-check-label" for="salary_type_monthly">Monthly</label>
                                </div>
                            </div>
                            @error('salary_type')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="reset" class="btn btn-light">Reset</button>
                        <button type="submit" class="btn btn-primary">Save Salary</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
