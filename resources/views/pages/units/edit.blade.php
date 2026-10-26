@extends('layouts.app')

@section('title', 'Edit Unit')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Unit</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('units.index') }}">Units</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('units.index') }}" class="btn btn-outline-light shadow-sm">Back to Units</a>
    </div>

    <form action="{{ route('units.update', $unit) }}" method="POST" class="row g-4" novalidate>
        @csrf
        @method('PUT')
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Edit Unit: {{ $unit->display_name }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Unit Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $unit->name) }}" @error('name') aria-describedby="name_error" aria-invalid="true" @enderror required>
                            @error('name')<div class="invalid-feedback d-block" id="name_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code', $unit->code) }}" @error('code') aria-describedby="code_error" aria-invalid="true" @enderror required>
                            @error('code')<div class="invalid-feedback d-block" id="code_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                                value="{{ old('description', $unit->description) }}" @error('description') aria-describedby="description_error" aria-invalid="true" @enderror>
                            @error('description')<div class="invalid-feedback d-block" id="description_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="d-flex align-items-center gap-2 mb-0">
                                <input type="hidden" name="active_status" value="0">
                                <input type="checkbox" name="active_status" class="form-check-input @error('active_status') is-invalid @enderror" value="1" @checked(old('active_status', $unit->active_status)) @error('active_status') aria-describedby="active_status_error" aria-invalid="true" @enderror>
                                Active
                            </label>
                            @error('active_status')<div class="invalid-feedback d-block" id="active_status_error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('units.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
