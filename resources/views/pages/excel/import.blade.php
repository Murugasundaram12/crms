@extends('layouts.app')
@section('title', $title)
@section('content')
    @include('partials.alerts')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4>{{ $title }}</h4>
            <form action="{{ $action }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Excel / CSV File <span class="text-danger">*</span></label>
                    <input class="form-control @error('file') is-invalid @enderror" type="file" name="file" accept=".xlsx,.xls,.csv" @error('file') aria-describedby="excel_import_file_error" aria-invalid="true" @enderror required>
                    @error('file')<div class="invalid-feedback d-block" id="excel_import_file_error">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary" type="submit">Import</button>
            </form>
            <p class="text-muted mt-3 mb-0">Required columns: name, phone, status. Existing phone numbers are updated.</p>
        </div>
    </div>
@endsection
