@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Attendance List</h4>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light shadow">Back to Dashboard</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.index') }}" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="user_id" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}"
                        placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}"
                        placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="checked_in" @selected(request('status') === 'checked_in')>Checked In Only</option>
                        <option value="checked_out" @selected(request('status') === 'checked_out')>Checked Out</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('attendance.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
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
                                        <span class="badge bg-success">Checked Out</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Checked In</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $attendances->links() }}
            </div>
        </div>
    </div>
@endsection
