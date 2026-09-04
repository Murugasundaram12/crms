@extends('layouts.app')

@section('title', 'Edit Labour Site Assignment')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Labour Site Assignment</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labours.index') }}">Labours</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour_assignments.index') }}">Labour Assignments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Assignment #{{ $assignment->id }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('labour_assignments.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="ti ti-arrow-left me-1"></i> Back to Assignments
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-edit text-primary me-2"></i>Assignment Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('labour_assignments.update', $assignment) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label required fw-bold">Labour</label>
                            <select name="labour_id" class="form-select @error('labour_id') is-invalid @enderror" required>
                                <option value="">Select Labour</option>
                                @foreach($labours as $l)
                                    <option value="{{ $l->id }}" @selected((string)old('labour_id', $assignment->labour_id) === (string)$l->id)>
                                        {{ $l->name }} {{ $l->labourRole ? '(' . $l->labourRole->name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('labour_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required fw-bold">Project / Site</label>
                            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                <option value="">Select Site</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" @selected((string)old('project_id', $assignment->project_id) === (string)$p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $assignment->start_date?->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $assignment->end_date?->format('Y-m-d')) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required fw-bold">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', $assignment->status) === 'active')>Active</option>
                                <option value="completed" @selected(old('status', $assignment->status) === 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status', $assignment->status) === 'cancelled')>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Optional assignment notes">{{ old('notes', $assignment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('labour_assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary shadow-sm">
                                <i class="ti ti-device-floppy me-1"></i> Update Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
