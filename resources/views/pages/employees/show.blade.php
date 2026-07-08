@extends('layouts.app')

@section('title', 'User Details')

@section('content')
    @include('partials.alerts')

    @php($isOwnProfile = request()->routeIs('profile.show'))

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">{{ $isOwnProfile ? 'My Profile' : 'User Details' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    @if($isOwnProfile)
                        <li class="breadcrumb-item active">{{ $employee->name }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Manage Users</a></li>
                        <li class="breadcrumb-item active">{{ $employee->name }}</li>
                    @endif
                </ol>
            </nav>
        </div>
        <a href="{{ $isOwnProfile ? route('dashboard') : route('employees.index') }}" class="btn btn-light">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ asset($employee->avatar ?: 'assets/img/users/user-01.jpg') }}"
                            class="rounded-circle" width="72" height="72" alt="{{ $employee->name }}">
                        <div>
                            <h5 class="mb-1">{{ $employee->name }}</h5>
                            <p class="mb-0 text-muted">{{ $employee->designation ?: 'Team Member' }}</p>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted">Email</span><span>{{ $employee->email }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Phone</span><span>{{ $employee->phone ?: '-' }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Role</span><span>{{ $employee->roles->pluck('name')->join(', ') ?: $employee->role }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Status</span><span class="badge {{ $employee->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($employee->status) }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Hire Date</span><span>{{ optional($employee->hire_date)->format('d M Y') ?: '-' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Expenses</p><h5 class="mb-0">Rs {{ number_format($stats['expense_total'], 2) }}</h5></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Paid</p><h5 class="mb-0">Rs {{ number_format($stats['paid_total'], 2) }}</h5></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Unpaid</p><h5 class="mb-0">Rs {{ number_format($stats['unpaid_total'], 2) }}</h5></div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">30 Day Hours</p><h5 class="mb-0">{{ $stats['worked_hours'] }}h {{ $stats['worked_minutes'] }}m</h5></div></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Expense History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Main Category</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Unpaid</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ optional($expense->current_date)->format('d M Y') ?: '-' }}</td>
                                <td>{{ $expense->project?->name ?: '-' }}</td>
                                <td>{{ $expense->mainCategory?->name ?: '-' }}</td>
                                <td>{{ $expense->category?->name ?: '-' }}</td>
                                <td>Rs {{ number_format((float) $expense->amount, 2) }}</td>
                                <td>Rs {{ number_format((float) $expense->paid_amt, 2) }}</td>
                                <td>Rs {{ number_format((float) $expense->unpaid_amt, 2) }}</td>
                                <td>{{ $expense->description ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No expenses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $expenses->links() }}
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Attendance History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Worked</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ optional($attendance->attendance_date)->format('d M Y') ?: '-' }}</td>
                                <td>{{ optional($attendance->check_in_at)->format('h:i A') ?: '-' }}</td>
                                <td>{{ optional($attendance->check_out_at)->format('h:i A') ?: '-' }}</td>
                                <td>{{ intdiv((int) $attendance->worked_minutes, 60) }}h {{ ((int) $attendance->worked_minutes) % 60 }}m</td>
                                <td><span class="badge bg-light text-dark">{{ ucfirst($attendance->status ?? 'pending') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No attendance found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $attendances->links() }}
        </div>
    </div>
@endsection
