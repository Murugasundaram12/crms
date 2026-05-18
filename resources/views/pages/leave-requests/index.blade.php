@extends('layouts.app')

@section('title', 'Leave Requests')
@section('content_class', 'pb-0')

@section('content')
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Leave Requests</h4>
        </div>
        <div>
            <a href="{{ route('leaveRequests.create') }}" class="btn btn-primary"><i
                    class="ti ti-square-rounded-plus-filled me-1"></i>Add Leave Request</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <form method="GET" action="{{ route('leaveRequests.index') }}">
                        <input type="text" name="q" class="form-control" placeholder="Search" />
                    </form>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <form method="GET" action="{{ route('leaveRequests.index') }}" class="d-flex gap-2">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="table-responsive custom-table table-nowrap">
                <table class="table table-nowrap" id="leave-request-list">
                    <thead class="table-light">
                        <tr>
                            <th>Leave ID</th>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Status</th>
                            <th class="no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveRequests as $leaveRequest)
                            <tr>
                                <td>{{ $leaveRequest->id }}</td>
                                <td>{{ $leaveRequest->user?->name }}</td>
                                <td>{{ $leaveRequest->leaveType?->name }}</td>
                                <td>{{ $leaveRequest->from_date?->format('Y-m-d') }}</td>
                                <td>{{ $leaveRequest->to_date?->format('Y-m-d') }}</td>
                                <td>
                                    @if($leaveRequest->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($leaveRequest->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('leaveRequests.show', $leaveRequest) }}">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No leave requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $leaveRequests->links() }}
            </div>
        </div>
    </div>
@endsection
