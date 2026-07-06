@extends('layouts.app')

@section('title', 'Variations')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Variations<span class="badge badge-soft-primary ms-2">{{ $variations->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Variations</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Variation</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('variations.index') }}" method="GET"
                class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-3">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="additional" @selected(request('type') === 'additional')>Additional</option>
                        <option value="deduction" @selected(request('type') === 'deduction')>Deduction</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-1">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('variations.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive table-nowrap custom-table">
                <table class="table table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Project</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variations as $variation)
                            <tr>
                                <td><span
                                        class="badge bg-{{ $variation->type === 'additional' ? 'success' : 'danger' }}">{{ ucfirst($variation->type) }}</span>
                                </td>
                                <td>{{ $variation->project?->name ?? '-' }}</td>
                                <td>{{ Str::limit($variation->description, 50) }}</td>
                                <td>${{ number_format($variation->amount, 2) }}</td>
                                <td>{{ $variation->date->format('d M Y') }}</td>
                                <td><span
                                        class="badge bg-{{ $variation->status === 'approved' ? 'success' : ($variation->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($variation->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                            data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_variation_{{ $variation->id }}"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <button type="button" class="dropdown-item crm-delete-trigger"
                                                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                data-delete-action="{{ route('variations.destroy', $variation) }}"
                                                data-delete-title="Delete Variation"
                                                data-delete-message="Are you sure you want to delete this variation?">
                                                <i class="ti ti-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No variations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $variations->links() }}</div>
        </div>
    </div>

    <!-- Offcanvas Add and Edit Modals similar to payment-stages, omitted for brevity -->
    <!-- Offcanvas Add -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Variation</h5><button type="button" class="btn-close"
                data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('variations.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" required>
                        <option value="">Select</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="">Select</option>
                        <option value="additional">Additional</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" min="0" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Approved By</label>
                    <select name="approved_by" class="form-select">
                        <option value="">Select Employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Variation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modals -->
    @foreach ($variations as $variation)
        <div class="modal fade" id="edit_variation_{{ $variation->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Variation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('variations.update', $variation) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select">
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected($variation->project_id === $project->id)>
                                            {{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="additional" @selected($variation->type === 'additional')>Additional</option>
                                    <option value="deduction" @selected($variation->type === 'deduction')>Deduction</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3">{{ $variation->description }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control"
                                    value="{{ $variation->amount }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control"
                                    value="{{ $variation->date->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Approved By</label>
                                <select name="approved_by" class="form-select">
                                    <option value="">Select</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected($variation->approved_by === $employee->id)>
                                            {{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending" @selected($variation->status === 'pending')>Pending</option>
                                    <option value="approved" @selected($variation->status === 'approved')>Approved</option>
                                    <option value="rejected" @selected($variation->status === 'rejected')>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Variation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
