@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Attendance List</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attendance</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light shadow">Back to Dashboard</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <form method="GET" action="{{ route('attendance.index') }}" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label">Employee</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="checked_in" @selected(request('status') === 'checked_in')>Checked In Only</option>
                        <option value="checked_out" @selected(request('status') === 'checked_out')>Checked Out</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Check In Time</th>
                            <th>Check Out Time</th>
                            <th>Worked Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->id }}</td>
                                <td>{{ $attendance->user?->name ?? '-' }}</td>
                                <td>{{ optional($attendance->attendance_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ optional($attendance->check_in_at)->format('Y-m-d h:i A') ?? '-' }}</td>
                                <td>{{ optional($attendance->check_out_at)->format('Y-m-d h:i A') ?? '-' }}</td>
                                <td>
                                    @if(!is_null($attendance->worked_minutes))
                                        {{ floor($attendance->worked_minutes / 60) }}h {{ $attendance->worked_minutes % 60 }}m
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($attendance->check_out_at)
                                        <span class="badge bg-soft-success text-success">Checked Out</span>
                                    @else
                                        <span class="badge bg-soft-warning text-warning">Checked In</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($attendances->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $attendances->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
