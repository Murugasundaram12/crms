@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Category</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('categories.index') }}" class="btn btn-outline-light shadow-sm">Back to Categories</a>
    </div>

    <form action="{{ route('categories.update', $category->id) }}" method="POST" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Edit Category: {{ $category->name }}</h5>
                    <p class="text-muted fs-13 mb-0">Update category information.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Main Category</label>
                            <select name="main_category_id"
                                class="form-select @error('main_category_id') is-invalid @enderror">
                                <option value="">No Main Category</option>
                                @foreach($mainCategories as $main)
                                    <option value="{{ $main->id }}" {{ old('main_category_id', $category->main_category_id) == $main->id ? 'selected' : '' }}>
                                        {{ $main->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('main_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
