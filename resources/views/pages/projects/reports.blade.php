@extends('layouts.app')

@section('title', 'Project Reports')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Project Reports<span class="badge badge-soft-primary ms-2">{{ $projects->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Project Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7 d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap row-gap-2">
                    <h6 class="mb-0">Projects By Status</h6>
                    <span class="btn btn-outline-light shadow">Live Summary</span>
                </div>
                <div class="card-body">
                    <div id="project-year">
                        <div class="row g-3">
                            @forelse ($summary['statusCounts'] as $status => $count)
                                <div class="col-md-4">
                                    <div class="border rounded p-3 bg-light h-100">
                                        <p class="mb-1">{{ ucfirst(str_replace('_', ' ', $status)) }}</p>
                                        <h5 class="mb-0">{{ $count }}</h5>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted">No status data yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5 d-flex">
            <div class="card shadow flex-fill">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap row-gap-2">
                    <h6 class="mb-0">Projects By Priority</h6>
                    <span class="btn btn-outline-light shadow">Current Mix</span>
                </div>
                <div class="card-body">
                    <div id="project-type">
                        @forelse ($summary['priorityCounts'] as $priority => $count)
                            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                                <p class="mb-0">{{ ucfirst($priority) }}</p>
                                <h6 class="mb-0">{{ $count }}</h6>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No priority data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-3 d-flex"><div class="card shadow flex-fill"><div class="card-body"><p class="mb-1">Total Budget</p><h5 class="mb-0">${{ number_format($summary['totalBudget'], 2) }}</h5></div></div></div>
        <div class="col-md-3 d-flex"><div class="card shadow flex-fill"><div class="card-body"><p class="mb-1">Total Spent</p><h5 class="mb-0">${{ number_format($summary['totalSpent'], 2) }}</h5></div></div></div>
        <div class="col-md-3 d-flex"><div class="card shadow flex-fill"><div class="card-body"><p class="mb-1">Paid Revenue</p><h5 class="mb-0">${{ number_format($summary['totalPayments'], 2) }}</h5></div></div></div>
        <div class="col-md-3 d-flex"><div class="card shadow flex-fill"><div class="card-body"><p class="mb-1">Expenses</p><h5 class="mb-0">${{ number_format($summary['totalExpenses'], 2) }}</h5></div></div></div>
    </div>

    <div class="card border-0 rounded-0 mt-3">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <form action="{{ route('reports.projects') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap w-100">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                </div>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Client</th>
                            <th>Priority</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></td>
                                <td>{{ $project->client?->name ?? '-' }}</td>
                                <td>{{ ucfirst($project->priority) }}</td>
                                <td>{{ optional($project->start_date)->format('d M Y') ?? '-' }}</td>
                                <td>{{ optional($project->end_date)->format('d M Y') ?? '-' }}</td>
                                <td>{{ $project->progress }}%</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No report data found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $projects->links() }}</div>
        </div>
    </div>
@endsection
