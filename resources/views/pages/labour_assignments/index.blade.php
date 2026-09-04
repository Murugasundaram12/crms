@extends('layouts.app')

@section('title', 'Labour Site Assignments')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Labour Site Assignments</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labours.index') }}">Labours</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Assignments</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('labour-attendances.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="ti ti-calendar-check me-1"></i> Labour Attendance
            </a>
            @if(auth()->user()?->hasPermission('labour-assignments-create'))
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                    <i class="ti ti-plus me-1"></i> New Assignment
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('labour_assignments.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Project / Site</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Labour</label>
                    <select name="labour_id" class="form-select">
                        <option value="">All Labours</option>
                        @foreach($labours as $l)
                            <option value="{{ $l->id }}" {{ request('labour_id') == $l->id ? 'selected' : '' }}>
                                {{ $l->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="ti ti-filter me-1"></i> Filter</button>
                    <a href="{{ route('labour_assignments.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Labour</th>
                            <th>Site / Project</th>
                            <th>Supervisor</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $assignment->labour?->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $assignment->labour?->labourRole?->name ?? '' }}</small>
                                </td>
                                <td>{{ $assignment->project?->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->employee?->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->start_date?->format('d M Y') }}</td>
                                <td>{{ $assignment->end_date?->format('d M Y') }}</td>
                                <td>
                                    @if($assignment->status === 'active')
                                        <span class="badge bg-soft-success text-success">Active</span>
                                    @elseif($assignment->status === 'completed')
                                        <span class="badge bg-soft-secondary text-secondary">Completed</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @if(auth()->user()?->hasPermission('labour-assignments-edit'))
                                            <a href="{{ route('labour_assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary border-0" title="Edit Assignment">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()?->hasPermission('labour-assignments-delete'))
                                            <form action="{{ route('labour_assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Delete Assignment">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No labour site assignments found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $assignments->links() }}
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('labour_assignments.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">New Labour Site Assignment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Labour</label>
                            <select name="labour_id" class="form-select" required>
                                <option value="">Select Labour</option>
                                @foreach($labours as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Project / Site</label>
                            <select name="project_id" class="form-select" required>
                                <option value="">Select Site</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ now()->addDays(6)->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Assignment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
