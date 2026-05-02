@extends('layouts.app')

@section('title', 'Create Labour')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Create Labour</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labours.index') }}">Labours</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('labours.index') }}" class="btn btn-outline-light shadow-sm">Back to Labours</a>
    </div>

    <form action="{{ route('labours.store') }}" method="POST" enctype="multipart/form-data" class="row g-4">
        @csrf

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Labour Details</h5>
                    <p class="text-muted fs-13 mb-0">Add a labour record and choose a labour role to auto-fill salary.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror"
                                value="{{ old('job_title') }}">
                            @error('job_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone_number"
                                class="form-control @error('phone_number') is-invalid @enderror"
                                value="{{ old('phone_number') }}" required>
                            @error('phone_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Labour Role <span class="text-danger">*</span></label>
                            <select name="labour_role_id" id="labour_role_id"
                                class="form-select @error('labour_role_id') is-invalid @enderror" required>
                                <option value="">Select Labour Role</option>
                                @foreach ($labourRoles as $labourRole)
                                    <option value="{{ $labourRole->id }}" data-salary="{{ $labourRole->salary }}"
                                        @selected((string) old('labour_role_id') === (string) $labourRole->id)>
                                        {{ $labourRole->name }} ({{ ucfirst($labourRole->salary_type) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('labour_role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Gender <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_male"
                                        value="male" @checked(old('gender') === 'male') required>
                                    <label class="form-check-label" for="gender_male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_female"
                                        value="female" @checked(old('gender') === 'female') required>
                                    <label class="form-check-label" for="gender_female">Female</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_other"
                                        value="other" @checked(old('gender') === 'other') required>
                                    <label class="form-check-label" for="gender_other">Other</label>
                                </div>
                            </div>
                            @error('gender')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Salary</label>
                            <input type="number" step="0.01" name="salary" id="salary"
                                class="form-control @error('salary') is-invalid @enderror"
                                value="{{ old('salary') }}" readonly>
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Government Photo</label>
                            <input type="file" name="government_photo"
                                class="form-control @error('government_photo') is-invalid @enderror" accept="image/*">
                            @error('government_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="reset" class="btn btn-light">Reset</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labourRoleField = document.getElementById('labour_role_id');
            const salaryField = document.getElementById('salary');

            const updateSalaryFromRole = () => {
                const selectedOption = labourRoleField.options[labourRoleField.selectedIndex];
                const roleSalary = selectedOption ? selectedOption.getAttribute('data-salary') : '';

                salaryField.value = roleSalary || '';
            };

            if (labourRoleField && salaryField) {
                labourRoleField.addEventListener('change', updateSalaryFromRole);
                updateSalaryFromRole();
            }
        });
    </script>
@endpush
