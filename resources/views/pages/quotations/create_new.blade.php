@extends('layouts.app')

@section('title', 'Create Quotation')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Create Quotation<span class="badge badge-soft-primary ms-2">{{ count($clients) }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quotations.list') }}">Quotations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('quotations.list') }}" class="btn btn-outline-light shadow-sm">Back to Quotations</a>
    </div>

    <form action="{{ route('quotations.store') }}" method="POST">
        @csrf

        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card border quotation-section-card">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-soft-primary text-primary"><i class="ti ti-file-description"></i></span>
                            <div>
                                <h5 class="mb-1">Quotation Details</h5>
                                <p class="text-muted mb-0">Only basic quotation details are enabled on this screen.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                <select name="client_id" class="form-select @error('client_id') is-invalid @enderror"
                                    required>
                                    <option value="">Select Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Validity Days <span class="text-danger">*</span></label>
                                <input type="number" name="validity_days"
                                    class="form-control @error('validity_days') is-invalid @enderror"
                                    value="{{ old('validity_days', 30) }}" min="1" max="365" required>
                                @error('validity_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration (Days) <span class="text-danger">*</span></label>
                                <input type="number" name="duration_days"
                                    class="form-control @error('duration_days') is-invalid @enderror"
                                    value="{{ old('duration_days', 1) }}" min="1" required>
                                @error('duration_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4"
                                    placeholder="Add internal notes if needed">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
            <a href="{{ route('quotations.list') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Quotation</button>
        </div>
    </form>
@endsection

@push('styles')
    <style>
        .quotation-section-card {
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
    </style>
@endpush
