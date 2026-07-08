@extends('layouts.app')

@section('title', 'Labours')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Labours<span class="badge badge-soft-primary ms-2">{{ $labours->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labours</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('labours-create')
                <a href="{{ route('labours.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Labour
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('labours.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-3">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search labours" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Role</label>
                    <select name="labour_role_id" class="form-select">
                        <option value="">All Roles</option>
                        @foreach ($labourRoles as $labourRole)
                            <option value="{{ $labourRole->id }}" @selected((string) request('labour_role_id') === (string) $labourRole->id)>
                                {{ $labourRole->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All Genders</option>
                        <option value="male" @selected(request('gender') === 'male')>Male</option>
                        <option value="female" @selected(request('gender') === 'female')>Female</option>
                        <option value="other" @selected(request('gender') === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('labours.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Job Title</th>
                            <th>Phone Number</th>
                            <th>Role</th>
                            <th>Gender</th>
                            <th>Salary</th>
                            <th>Wallet / Advance</th>
                            <th>Photo</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($labours as $labour)
                            <tr>
                                <td>{{ $labour->name }}</td>
                                <td>{{ $labour->job_title ?: '-' }}</td>
                                <td>{{ $labour->phone_number }}</td>
                                <td>{{ $labour->labourRole?->name ?: '-' }}</td>
                                <td>{{ ucfirst($labour->gender) }}</td>
                                <td>Rs {{ number_format((float) $labour->salary, 2) }}</td>
                                <td class="text-info fw-semibold">Rs. {{ number_format((float) $labour->advance_amt, 2) }}</td>
                                <td>
                                    @if ($labour->government_photo)
                                        <a href="{{ asset('storage/' . $labour->government_photo) }}" target="_blank"
                                            class="btn btn-sm btn-outline-light">View</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('labours.edit', $labour->id)"
                                        editPermission="labours-edit"
                                        :deleteRoute="route('labours.delete', $labour->id)"
                                        deleteTitle="Delete Labour"
                                        :deleteMessage="'Are you sure you want to delete labour \'' . $labour->name . '\'?'"
                                        deletePermission="labours-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No labour records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($labours->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $labours->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
