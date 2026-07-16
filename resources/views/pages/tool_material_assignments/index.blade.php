@extends('layouts.app')

@section('title', 'Tool / Material Transfers')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tool / Material Transfers<span class="badge badge-soft-primary ms-2">{{ $assignments->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tool / Material Transfers</li>
                </ol>
            </nav>
        </div>
        @can('tools-materials-create')
            <a href="{{ route('tools-material-assignments.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Assign / Transfer
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('tools-material-assignments.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Tool Name</label>
                    <select name="tool_material_id" class="form-select">
                        <option value="">All Tools</option>
                        @foreach($toolsMaterials as $tool)
                            <option value="{{ $tool->id }}" @selected((string) request('tool_material_id') === (string) $tool->id)>{{ $tool->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Site Name</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Sites</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Transfer</label>
                    <select name="transfer_type" class="form-select">
                        <option value="">All</option>
                        <option value="site_to_office" @selected(request('transfer_type') === 'site_to_office')>Site to Office</option>
                        <option value="site_to_site" @selected(request('transfer_type') === 'site_to_site')>Site to Site</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('tools-material-assignments.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tool Name</th>
                            <th>Site Name</th>
                            <th>Transfer</th>
                            <th>To</th>
                            <th>Date & Time</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="fw-semibold">{{ $assignment->toolMaterial?->name ?? '-' }}</td>
                                <td>{{ $assignment->fromProject?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-soft-info text-info">
                                        {{ $assignment->transfer_type === 'site_to_site' ? 'Site to Site' : 'Site to Office' }}
                                    </span>
                                </td>
                                <td>{{ $assignment->transfer_type === 'site_to_site' ? ($assignment->toProject?->name ?? '-') : 'Office' }}</td>
                                <td>{{ $assignment->transferred_at?->format('d M Y h:i A') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('tools-material-assignments.edit', $assignment)"
                                        editPermission="tools-materials-edit"
                                        :deleteRoute="route('tools-material-assignments.destroy', $assignment)"
                                        deleteTitle="Delete Transfer"
                                        :deleteMessage="'Are you sure you want to delete this transfer?'"
                                        deletePermission="tools-materials-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No transfer records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($assignments->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $assignments->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
