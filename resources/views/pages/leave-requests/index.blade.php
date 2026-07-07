@extends('layouts.app')

@section('title', 'Leave Requests')
@section('content_class', 'pb-0')

@section('content')
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Leave Requests</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave Requests</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('leaveRequests.create') }}" class="btn btn-primary shadow-sm"><i
                    class="ti ti-square-rounded-plus-filled me-1"></i>Add Leave Request</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
                <form method="GET" action="{{ route('leaveRequests.index') }}" class="row g-3 align-items-end m-0">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Search</label>
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                        <a href="{{ route('leaveRequests.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                    </div>
                </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive custom-table table-nowrap">
                <table class="table table-hover table-nowrap align-middle mb-0" id="leave-request-list">
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
                                        <span class="badge bg-soft-warning text-warning">Pending</span>
                                    @elseif($leaveRequest->status === 'approved')
                                        <span class="badge bg-soft-success text-success">Approved</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" title="Details"
                                        href="{{ route('leaveRequests.show', $leaveRequest) }}">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No leave requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($leaveRequests->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $leaveRequests->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
