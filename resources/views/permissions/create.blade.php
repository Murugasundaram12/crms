@extends('layouts.app')

@section('title', 'Create Permission')

@section('content')
<!-- Page Header -->
<div class="mb-4">
    <h4 class="mb-1">Create New Permission</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 p-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Permission Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('permissions.store') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-form-label">Key <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="text" class="form-control @error('key') is-invalid @enderror" name="key" value="{{ old('key') }}" required placeholder="e.g., user.create">
                                    <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted">
                                        <i class="ti ti-key"></i>
                                    </span>
                                </div>
                                @error('key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
