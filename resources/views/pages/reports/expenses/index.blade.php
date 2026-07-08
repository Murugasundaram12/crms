@extends('layouts.app')

@section('title', 'Expense Report')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Expense Report</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Expense Report</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form method="GET" action="{{ route('expenseReports.index') }}" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"
                        placeholder="Date From">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"
                        placeholder="Date To">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" value="{{ request('category') }}" class="form-control"
                        placeholder="Category">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        <option value="recorded" @selected(request('status') === 'recorded')>Recorded</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('expenseReports.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar avatar-lg bg-soft-primary text-primary flex-shrink-0">
                        <i class="ti ti-files fs-22"></i>
                    </span>
                    <div>
                        <p class="mb-1 text-muted">Total Records</p>
                        <h5 class="mb-0">{{ $totals['count'] }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar avatar-lg bg-soft-success text-success flex-shrink-0">
                        <i class="ti ti-cash fs-22"></i>
                    </span>
                    <div>
                        <p class="mb-1 text-muted">Total Amount</p>
                        <h5 class="mb-0">Rs {{ number_format($totals['amount'], 2) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Source</th>
                            <th>Project</th>
                            <th>Employee</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense['id'] }}</td>
                                <td>{{ optional($expense['date'])->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $expense['source'] }}</td>
                                <td>{{ $expense['project'] }}</td>
                                <td>{{ $expense['employee'] }}</td>
                                <td>{{ $expense['title'] }}</td>
                                <td>{{ $expense['category'] }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($expense['status']) {
                                            'approved', 'recorded' => 'bg-soft-success text-success',
                                            'rejected' => 'bg-soft-danger text-danger',
                                            default => 'bg-soft-warning text-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($expense['status']) }}</span>
                                </td>
                                <td class="text-end">Rs {{ number_format((float) $expense['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No expense records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($expenses->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $expenses->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
