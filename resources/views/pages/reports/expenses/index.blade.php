@extends('layouts.app')

@section('title', 'Expense Report')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Expense Report</h4>
            <p class="mb-0 text-muted">Track all expenses with filters and totals.</p>
        </div>
    </div>

    <div class="card border rounded-0 mb-4">
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

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <p class="mb-1 text-muted">Total Records</p>
                    <h5 class="mb-0">{{ $totals['count'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <p class="mb-1 text-muted">Total Amount</p>
                    <h5 class="mb-0">Rs {{ number_format($totals['amount'], 2) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
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
                                <td>{{ ucfirst($expense['status']) }}</td>
                                <td class="text-end">Rs {{ number_format((float) $expense['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No expense records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
@endsection
