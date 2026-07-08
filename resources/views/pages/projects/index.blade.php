@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Projects<span class="badge badge-soft-primary ms-2">{{ $projects->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Projects</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            @can('projects-create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Project</a>
            @endcan
        </div>
    </div>

    <div class="card border rounded-0 mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('projects.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-xl-3">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search projects"
                            value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach (['planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select">
                        <option value="">All Clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-1">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All</option>
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-1">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @forelse ($projects as $project)
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span
                                    class="badge badge-tag badge-soft-{{ $project->priority === 'high' ? 'danger text-danger' : ($project->priority === 'medium' ? 'warning text-warning' : 'success text-success') }} me-2 border-0">
                                    <i class="ti ti-square-rounded-filled fs-8 me-1"></i>{{ ucfirst($project->priority) }}
                                </span>
                                <span
                                    class="badge {{ $project->status === 'completed' ? 'bg-success' : ($project->status === 'active' ? 'bg-info' : 'bg-secondary') }}">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
                            </div>
                            <span class="avatar avatar-xs fs-16">
                                <i class="ti ti-star-filled text-warning"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between bg-light rounded p-2 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{ route('projects.show', $project) }}"
                                    class="avatar border rounded-circle bg-white flex-shrink-0 me-2">
                                    <img src="{{ asset('assets/img/icons/company-icon-0' . (($loop->iteration % 5) + 1) . '.svg') }}"
                                        class="w-auto h-auto" alt="img">
                                </a>
                                <div>
                                    <h5 class="fw-medium fs-14"><a
                                            href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></h5>
                                    <p class="fs-13 mb-0">{{ $project->type }}</p>
                                </div>
                            </div>
                            <div class="dropdown table-action">
                                <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @can('projects-edit')
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#edit_project_{{ $project->id }}"><i class="ti ti-edit text-blue"></i>
                                            Edit</a>
                                    @endcan
                                    <a class="dropdown-item" href="{{ route('projects.show', $project) }}"><i
                                            class="ti ti-eye text-blue"></i> View Details</a>
                                    @can('projects-delete')
                                        <button type="button" class="dropdown-item crm-delete-trigger" data-bs-toggle="modal"
                                            data-bs-target="#crmDeleteModal"
                                            data-delete-action="{{ route('projects.destroy', $project) }}"
                                            data-delete-title="Delete Project"
                                            data-delete-message="Are you sure you want to delete project '{{ $project->name }}'?">
                                            <i class="ti ti-trash"></i> Delete
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        <div class="d-block">
                            <p class="mb-3">
                                {{ \Illuminate\Support\Str::limit($project->description ?: 'Construction project for site execution, coordination, and delivery.', 95) }}
                            </p>
                            <div class="mb-3">
                                <p class="d-flex align-items-center mb-2"><i class="ti ti-forbid-2 me-2"></i>Project ID :
                                    {{ $project->project_code }}</p>
                                <p class="d-flex align-items-center mb-2"><i class="ti ti-map-pin me-2"></i>Location:
                                    @if($project->location)<a href="{{ $project->location }}" target="_blank" rel="noopener"
                                    class="text-primary">View Site</a>@else - @endif</p>
                                <p class="d-flex align-items-center mb-2"><i class="ti ti-calendar-exclamation me-2"></i>Due
                                    Date : {{ optional($project->end_date)->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <span class="badge bg-light text-dark">Client: {{ $project->client?->name ?? '-' }}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm p-1 border flex-shrink-0 rounded-circle">
                                        <img src="{{ asset('assets/img/icons/company-icon-0' . (($loop->iteration % 5) + 1) . '.svg') }}"
                                            alt="img">
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <span class="badge badge-sm bg-soft-info text-info"><i class="ti ti-clock-stop me-2"></i>Progress :
                                {{ $project->progress }}%</span>
                            <div class="d-flex align-items-center">
                                <span class="d-inline-flex align-items-center me-2"><i
                                        class="ti ti-user me-1"></i>{{ $project->manager?->name ?? 'Unassigned' }}</span>
                                <span class="d-inline-flex align-items-center"><i
                                        class="ti ti-subtask me-1"></i>{{ $project->tasks_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">No projects created yet</h5>
                        <p class="text-muted mb-3">Start by creating your first construction project.</p>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvas_add">Add New Project</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="load-btn text-center mt-3">
        {{ $projects->links() }}
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add New Project</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('projects.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Project Code</label>
                    <input type="text" name="project_code" class="form-control"
                        value="{{ old('project_code', 'PRJ-' . now()->format('His')) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Project Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Employee</label>
                    <select name="manager_id" class="form-select">
                        <option value="">Select</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <input type="text" name="type" class="form-control" value="{{ old('type', 'Construction') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="planning">Planning</option>
                        <option value="active">Active</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Progress %</label>
                    <input type="number" min="0" max="100" name="progress" class="form-control"
                        value="{{ old('progress', 0) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Location (URL)</label>
                    <input type="url" name="location" class="form-control" value="{{ old('location') }}" placeholder="https://maps.google.com/...">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($projects as $project)
        <div class="modal fade" id="edit_project_{{ $project->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('projects.update', $project) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-md-6">
                                <label class="form-label">Project Code</label>
                                <input type="text" name="project_code" class="form-control" value="{{ $project->project_code }}"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Project Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $project->name }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Client</label>
                                <select name="client_id" class="form-select" required>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected($client->id === $project->client_id)>
                                            {{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Manager</label>
                                <select name="manager_id" class="form-select">
                                    <option value="">Select</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected($employee->id === $project->manager_id)>
                                            {{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <input type="text" name="type" class="form-control" value="{{ $project->type }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    @foreach (['low', 'medium', 'high'] as $priority)
                                        <option value="{{ $priority }}" @selected($project->priority === $priority)>
                                            {{ ucfirst($priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach (['planning', 'active', 'on_hold', 'completed', 'cancelled'] as $status)
                                        <option value="{{ $status }}" @selected($project->status === $status)>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Progress %</label>
                                <input type="number" min="0" max="100" name="progress" class="form-control"
                                    value="{{ $project->progress }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ optional($project->start_date)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ optional($project->end_date)->format('Y-m-d') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" value="{{ $project->location }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"
                                    rows="4">{{ $project->description }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Project</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
@endpush
