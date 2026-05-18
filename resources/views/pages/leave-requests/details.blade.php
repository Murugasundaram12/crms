@extends('layouts.app')

@section('title', 'Leave Request Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h4 class="mb-0">Leave Request #{{ $leaveRequest->id }}</h4>
                        <div class="text-muted">Submitted by {{ $leaveRequest->user?->name }}</div>
                    </div>
                    <div>
                        <a href="{{ route('leaveRequests.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Leave Type</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->leaveType?->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">From</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->from_date?->format('Y-m-d') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">To</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->to_date?->format('Y-m-d') }}</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Employee Remarks</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->remarks ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                @if($leaveRequest->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($leaveRequest->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Approver Remarks</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->approver_remarks ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Approved By</label>
                                <div class="form-control-plaintext">{{ $leaveRequest->approvedBy?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Approved At</label>
                                <div class="form-control-plaintext">
                                    {{ $leaveRequest->approved_at ? $leaveRequest->approved_at->format('Y-m-d H:i:s') : '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Document</label>
                                @if(!empty($leaveRequest->document))
                                @php($docUrl = str_starts_with($leaveRequest->document, '/storage/') ? $leaveRequest->document : $leaveRequest->document)
                                    <a class="btn btn-sm btn-primary" href="{{ $docUrl }}" target="_blank">View Document</a>
                                @else
                                <div class="text-muted">No document uploaded</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($leaveRequest->status === 'pending' && auth()->user()?->hasPermission('leave-requests-edit'))
                        <hr>
                        <h5 class="mb-3">Approve / Reject</h5>
                        <form method="POST" action="{{ route('leaveRequests.action', $leaveRequest) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Action</label>
                                    <select name="status" class="form-control">
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Approver Remarks</label>
                                    <input type="text" name="approverRemarks" class="form-control" placeholder="Remarks" />
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    @elseif($leaveRequest->status !== 'pending')
                        <div class="alert alert-info mt-3">This leave request has already been processed.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
