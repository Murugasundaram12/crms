@extends('layouts.app')

@section('title', 'Edit Main Category')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Main Category</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('main_categories.index') }}">Main Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('main_categories.index') }}" class="btn btn-outline-light shadow-sm">Back to Main Categories</a>
    </div>

    <form action="{{ route('main_categories.update', $mainCategory->id) }}" method="POST" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Edit Main Category: {{ $mainCategory->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $mainCategory->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('main_categories.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                </div>
            </div>
        </div>
    </form>
@endsection
