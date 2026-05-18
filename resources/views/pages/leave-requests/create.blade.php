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

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('leaveRequests.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-control" required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">From Date <span class="text-danger">*</span></label>
                        <input type="date" name="from_date" class="form-control" value="{{ old('from_date') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">To Date <span class="text-danger">*</span></label>
                        <input type="date" name="to_date" class="form-control" value="{{ old('to_date') }}" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Optional remarks">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Document</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Allowed: PDF, JPG, JPEG, PNG (Max: 5MB)</small>
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

