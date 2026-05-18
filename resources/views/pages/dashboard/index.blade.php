@extends('layouts.app')

@section('title', 'Dashboard')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-0">Dashboard</h4>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <div class="daterangepick form-control w-auto d-flex align-items-center me-2">
                <i class="ti ti-calendar text-dark me-2"></i>
                <span class="reportrange-picker-field text-dark">Construction Overview</span>
            </div>
            <a href="{{ route('projects.index') }}" class="btn btn-outline-light shadow">Projects</a>
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-light shadow">Tasks</a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-light shadow">Payments</a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1">Today's Attendance</h5>
                        @if($todayAttendance)
                            <p class="mb-0 text-muted">
                                Check-in: {{ optional($todayAttendance->check_in_at)->format('h:i A') ?? '-' }}
                                |
                                Check-out: {{ optional($todayAttendance->check_out_at)->format('h:i A') ?? '-' }}
                            </p>
                        @else
                            <p class="mb-0 text-muted">No check-in yet for today.</p>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if(auth()->user()?->hasPermission('attendance-list'))
                            <a href="{{ route('attendance.index') }}" class="btn btn-outline-light">Attendance List</a>
                        @endif
                        @if(!$todayAttendance)
                            <form action="{{ route('attendance.check-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">Check In</button>
                            </form>
                        @elseif(!$todayAttendance->check_out_at)
                            <form action="{{ route('attendance.check-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning">Check Out</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-outline-secondary" disabled>Attendance Completed</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-8 col-xl-7 d-flex">
            <div class="card flex-fill">
                <div class="card-body pb-0">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                class="line-title d-block me-2"></span>Revenue Analytics</h5>
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-light shadow">Project Reports</a>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">₹{{ number_format($summary['netRevenue'], 2) }}</h4>
                            <p class="mb-0">Net revenue after total expenses</p>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center border rounded px-2 py-1">
                                <p class="d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled fs-8 text-primary me-1"></i>Budget</p>
                            </div>
                            <div class="d-flex align-items-center border rounded px-2 py-1">
                                <p class="d-flex align-items-center mb-0"><i
                                        class="ti ti-circle-filled fs-8 text-light-500 me-1"></i>Spent</p>
                            </div>
                        </div>
                    </div>
                    <div id="performance-stats" class="py-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <p class="mb-1">Total Budget</p>
                                    <h5 class="mb-0">₹{{ number_format($summary['totalBudget'], 2) }}</h5>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <p class="mb-1">Budget Utilization</p>
                                    <h5 class="mb-0">{{ $summary['budgetUtilization'] }}%</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-5 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                        <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                class="line-title d-block me-2"></span>Operations Snapshot</h5>
                        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-icon btn-outline-light"><i
                                class="ti ti-arrow-right"></i></a>
                    </div>
                    <div id="traffic-sources-chart" class="py-3">
                        <div class="px-0 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-success fs-8 me-1"></i>Projects</p>
                            <p class="text-dark fw-semibold mb-0">{{ $summary['projectCount'] }}</p>
                        </div>
                        <div class="px-0 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-info fs-8 me-1"></i>Clients</p>
                            <p class="text-dark fw-semibold mb-0">{{ $summary['clientCount'] }}</p>
                        </div>
                        <div class="px-0 py-2 d-flex align-items-center justify-content-between border-bottom">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-warning fs-8 me-1"></i>Employees</p>
                            <p class="text-dark fw-semibold mb-0">{{ $summary['employeeCount'] }}</p>
                        </div>
                        <div class="px-0 pt-2 pb-3 d-flex align-items-center justify-content-between">
                            <p class="text-dark d-flex align-items-center mb-0"><i
                                    class="ti ti-circle-filled text-purple fs-8 me-1"></i>Task Completion</p>
                            <p class="text-dark fw-semibold mb-0">{{ $summary['completionRate'] }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body position-relative">
                    <p class="fw-medium mb-1">Revenue</p>
                    <h4 class="mb-3">₹{{ number_format($summary['netRevenue'], 2) }}</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span
                            class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">{{ $summary['completionRate'] }}%</span>
                        <p class="text-dark mb-0">After expenses deduction</p>
                    </div>
                    <div class="custom-card-icon">
                        <div class="avatar avatar-rounded avatar-lg bg-primary-gradient-100 position-absolute top-0 end-0">
                            <img src="{{ asset('assets/img/icons/revenue-icon.svg') }}" alt="icon"
                                class="img-fluid w-auto h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body position-relative">
                    <p class="fw-medium mb-1">Active Projects</p>
                    <h4 class="mb-3">{{ $summary['projectCount'] }}</h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span
                            class="d-inline-flex align-items-center badge rounded-pill badge-soft-info border-0">{{ $summary['pendingTasks'] }}</span>
                        <p class="text-dark mb-0">Open Tasks</p>
                    </div>
                    <div class="custom-card-icon">
                        <div class="avatar avatar-rounded avatar-lg bg-info-gradient-100 position-absolute top-0 end-0">
                            <img src="{{ asset('assets/img/icons/deal-icon.svg') }}" alt="icon"
                                class="img-fluid w-auto h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body position-relative">
                    <p class="fw-medium mb-1">Expenses</p>
                    <h4 class="mb-3">₹{{ number_format($summary['totalExpenses'], 2) }}</h4>
                    {{-- <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span
                            class="d-inline-flex align-items-center badge rounded-pill badge-soft-warning border-0">{{ $summary['budgetUtilization'] }}%</span>
                        <p class="text-dark mb-0">Includes labour + employee salaries</p>
                    </div>
                    <p class="text-muted mt-2 mb-0 fs-12">
                        Expense: ₹{{ number_format($summary['expenseOnlyTotal'], 2) }} |
                        Employee Salary: ₹{{ number_format($summary['employeeSalaryTotal'], 2) }} |
                        Labour Salary: ₹{{ number_format($summary['labourSalaryTotal'], 2) }}
                    </p> --}}
                    <div class="custom-card-icon">
                        <div class="avatar avatar-rounded avatar-lg bg-pink-gradient-100 position-absolute top-0 end-0">
                            <img src="{{ asset('assets/img/icons/conversion-icon.svg') }}" alt="icon"
                                class="img-fluid w-auto h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body position-relative">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-1">
                                <h4 class="mb-0">{{ $summary['taskCount'] }}</h4>
                                <span
                                    class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">{{ $summary['completedTasks'] }}</span>
                            </div>
                            <p class="fw-medium mb-1">Total Tasks</p>
                        </div>
                    </div>
                    <div class="d-flex alig-items-center gap-2">
                        <p class="text-dark mb-0">Completed tasks across all construction projects</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-6 col-xl-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
                        <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                class="line-title d-block me-2"></span>Recent Projects</h5>
                        <a class="btn btn-sm btn-light d-inline-flex align-items-center"
                            href="{{ route('projects.index') }}">
                            View All<i class="ti ti-chevron-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive custom-table">
                        <table class="table table-bordered table-nowrap">
                            <thead class="table-white">
                                <tr>
                                    <th>Project</th>
                                    <th>Client</th>
                                    <th>Added By</th>
                                    <th>Status</th>
                                    <th>Tasks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentProjects as $project)
                                    <tr>
                                        <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                                        <td>{{ $project->client?->name ?? '-' }}</td>
                                        <td>{{ $project->manager?->name ?? '-' }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</td>
                                        <td>{{ $project->tasks_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No projects available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-xl-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
                        <h5 class="mb-0 fs-16 fw-bold d-inline-flex items-center"><span
                                class="line-title d-block me-2"></span>Recent Tasks</h5>
                        <a class="btn btn-sm btn-light d-inline-flex align-items-center" href="{{ route('tasks.index') }}">
                            View All<i class="ti ti-chevron-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive custom-table">
                        <table class="table table-bordered table-nowrap">
                            <thead class="table-white">
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Assigned</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTasks as $task)
                                    <tr>
                                        <td>{{ $task->title }}</td>
                                        <td>{{ $task->project?->name ?? '-' }}</td>
                                        <td>{{ $task->employee?->name ?? '-' }}</td>
                                        <td>{{ optional($task->due_date)->format('d M Y') ?? '-' }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $task->status)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No tasks available yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}">
    </script>
@endpush
