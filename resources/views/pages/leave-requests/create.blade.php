@extends('layouts.app')

@section('title', 'Create Leave Request')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1">Create Leave Request</h4>
        </div>
        <a href="{{ route('leaveRequests.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @include('partials.alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('leaveRequests.store') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" @error('user_id') aria-describedby="leave_user_id_error" aria-invalid="true" @enderror required>
                            <option value="">Select Employee</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback d-block" id="leave_user_id_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" @error('leave_type_id') aria-describedby="leave_leave_type_id_error" aria-invalid="true" @enderror required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <div class="invalid-feedback d-block" id="leave_leave_type_id_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">From Date <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control @error('from_date') is-invalid @enderror" value="{{ old('from_date') }}" @error('from_date') aria-describedby="leave_from_date_error" aria-invalid="true" @enderror required>
                        @error('from_date')
                            <div class="invalid-feedback d-block" id="leave_from_date_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">To Date <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control @error('to_date') is-invalid @enderror" value="{{ old('to_date') }}" @error('to_date') aria-describedby="leave_to_date_error" aria-invalid="true" @enderror required>
                        @error('to_date')
                            <div class="invalid-feedback d-block" id="leave_to_date_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="4" placeholder="Optional remarks" @error('remarks') aria-describedby="leave_remarks_error" aria-invalid="true" @enderror>{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback d-block" id="leave_remarks_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Document</label>
                        <input type="file" name="document" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" @error('document') aria-describedby="leave_document_error" aria-invalid="true" @enderror>
                        <small class="text-muted">Allowed: PDF, JPG, JPEG, PNG (Max: 5MB)</small>
                        @error('document')
                            <div class="invalid-feedback d-block" id="leave_document_error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Leave Request</button>
                    <a href="{{ route('leaveRequests.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

