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
                                            <form action="{{ route('labour_assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this assignment?');" novalidate>
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
            <form action="{{ route('labour_assignments.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="_modal_target" value="#createAssignmentModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">New Labour Site Assignment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Labour <span class="text-danger">*</span></label>
                            <select name="labour_id" class="form-select @error('labour_id') is-invalid @enderror" @error('labour_id') aria-describedby="assignment_labour_id_error" aria-invalid="true" @enderror required>
                                <option value="">Select Labour</option>
                                @foreach($labours as $l)
                                    <option value="{{ $l->id }}" @selected(old('labour_id') == $l->id)>{{ $l->name }}</option>
                                @endforeach
                            </select>
                            @error('labour_id')<div class="invalid-feedback d-block" id="assignment_labour_id_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Project / Site <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" @error('project_id') aria-describedby="assignment_project_id_error" aria-invalid="true" @enderror required>
                                <option value="">Select Site</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('project_id')<div class="invalid-feedback d-block" id="assignment_project_id_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" @error('start_date') aria-describedby="assignment_start_date_error" aria-invalid="true" @enderror required>
                                @error('start_date')<div class="invalid-feedback d-block" id="assignment_start_date_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->addDays(6)->toDateString()) }}" @error('end_date') aria-describedby="assignment_end_date_error" aria-invalid="true" @enderror required>
                                @error('end_date')<div class="invalid-feedback d-block" id="assignment_end_date_error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" @error('status') aria-describedby="assignment_status_error" aria-invalid="true" @enderror required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                                <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block" id="assignment_status_error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" @error('notes') aria-describedby="assignment_notes_error" aria-invalid="true" @enderror>{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback d-block" id="assignment_notes_error">{{ $message }}</div>@enderror
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

    @if ($errors->any() && old('_modal_target'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var target = @json(old('_modal_target'));
                var el = document.querySelector(target);
                if (el) {
                    if (el.classList.contains('modal')) {
                        (new bootstrap.Modal(el)).show();
                    } else if (el.classList.contains('offcanvas')) {
                        (new bootstrap.Offcanvas(el)).show();
                    }
                }
            });
        </script>
    @endif
@endsection
