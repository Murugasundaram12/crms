@extends('layouts.app')

@section('title', 'Edit Payment Method')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Payment Method</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('payment-methods.index') }}">Payment Methods</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Payment Method</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('payment-methods.update', $paymentMethod) }}" method="POST" novalidate>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label required">Payment Method Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $paymentMethod->name) }}" @error('name') aria-describedby="edit_name_error" aria-invalid="true" @enderror required>
                        @error('name')
                            <div class="invalid-feedback d-block" id="edit_name_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="edit_code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $paymentMethod->code) }}" @error('code') aria-describedby="edit_code_error" aria-invalid="true" @enderror>
                        @error('code')
                            <div class="invalid-feedback d-block" id="edit_code_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" id="edit_type" class="form-control @error('type') is-invalid @enderror" value="{{ old('type', $paymentMethod->type) }}" @error('type') aria-describedby="edit_type_error" aria-invalid="true" @enderror>
                        @error('type')
                            <div class="invalid-feedback d-block" id="edit_type_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $paymentMethod->sort_order) }}" min="0" @error('sort_order') aria-describedby="edit_sort_order_error" aria-invalid="true" @enderror>
                        @error('sort_order')
                            <div class="invalid-feedback d-block" id="edit_sort_order_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input @error('active_status') is-invalid @enderror" type="checkbox" name="active_status" id="edit_active_status" value="1" {{ old('active_status', $paymentMethod->active_status) ? 'checked' : '' }} @error('active_status') aria-describedby="edit_active_status_error" aria-invalid="true" @enderror>
                            <label class="form-check-label fw-semibold" for="edit_active_status">Active Status</label>
                        </div>
                        @error('active_status')
                            <div class="invalid-feedback d-block" id="edit_active_status_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update Payment Method</button>
                        <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
