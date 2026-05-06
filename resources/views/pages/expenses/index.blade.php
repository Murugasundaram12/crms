@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Expenses<span class="badge badge-soft-primary ms-2">{{ $expenses->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Expenses</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <form action="{{ route('expenses.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search expenses"
                        value="{{ request('q') }}">
                </div>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                </select>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
            @can('expenses-create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Expense
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        @forelse ($expenses as $expense)
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card border shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span
                                    class="badge badge-tag badge-soft-{{ $expense->status === 'paid' ? 'success' : ($expense->status === 'approved' ? 'warning' : 'danger') }} me-2">
                                    {{ ucfirst($expense->status) }}
                                </span>
                                <span class="badge badge-tag badge-soft-info">{{ ucfirst($expense->type) }}</span>
                            </div>
                            <div class="dropdown">
                                <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @can('expenses-edit')
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#edit_expense_{{ $expense->id }}">
                                            <i class="ti ti-edit text-blue"></i> Edit
                                        </a>
                                    @endcan
                                    @can('expenses-delete')
                                        <button class="dropdown-item crm-delete-trigger" data-bs-toggle="modal"
                                            data-bs-target="#crmDeleteModal"
                                            data-delete-action="{{ route('expenses.destroy', $expense) }}"
                                            data-delete-title="Delete Expense" data-delete-message="Delete {{ $expense->title }}?">
                                            <i class="ti ti-trash"></i> Delete
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-medium">{{ $expense->title }}</h6>
                        <p class="text-muted mb-3">{{ $expense->category }} - {{ $expense->expense_date->format('d M Y') }}</p>
                        <div class="d-flex justify-content-between">
                            <span class="h6 mb-0">₹{{ number_format($expense->amount, 2) }}</span>
                            <div>
                                @if($expense->project)<span class="badge bg-light text-dark">Project:
                                {{ $expense->project->name }}</span>@endif
                                @if($expense->employee)<span class="badge bg-light text-dark ms-1">By:
                                {{ $expense->employee->name }}</span>@endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">No expenses recorded</h5>
                        <p class="text-muted mb-3">Add your first expense.</p>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvas_add">Add Expense</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $expenses->links() }}

    <div class="offcanvas offcanvas-end" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Expense</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('expenses.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="salary">Salary</option>
                        <option value="material">Material</option>
                        <option value="travel">Travel</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <input type="text" name="category" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="expense_date" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">None</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select">
                        <option value="">None</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($expenses as $expense)
        <div class="modal fade" id="edit_expense_{{ $expense->id }}">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Edit {{ $expense->title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('expenses.update', $expense) }}" method="POST" class="row g-3">
                            @csrf @method('PUT')
                            <div class="col-12">
                                <label>Title</label>
                                <input type="text" name="title" value="{{ $expense->title }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Type</label>
                                <select name="type" class="form-select" required>
                                    <option value="salary" @selected($expense->type === 'salary')>Salary</option>
                                    <option value="material" @selected($expense->type === 'material')>Material</option>
                                    <option value="travel" @selected($expense->type === 'travel')>Travel</option>
                                    <option value="other" @selected($expense->type === 'other')>Other</option>
                                </select>
                            </div>
                            <!-- similar fields for category, amount, date, project, employee, status, notes -->
                            <div class="col-12 d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection