@extends('layouts.app')

@section('title', 'Projects Details')
@section('content_class', 'pb-0')

    @section('content')
        @include('partials.alerts')

        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">{{ $project->name }}<span class="badge badge-soft-primary ms-2">{{ $project->tasks_count }} Tasks</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $project->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <a href="{{ route('projects.index') }}" class="btn btn-outline-light shadow"><i
                        class="ti ti-arrow-left me-1"></i>Back</a>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#edit_project_detail"><i
                        class="ti ti-edit me-1"></i>Edit Project</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body pb-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-xxl p-2 avatar-rounded border me-3 flex-shrink-0">
                                    <img src="{{ asset('assets/img/icons/company-icon-01.svg') }}" alt="img"
                                        class="avatar avtart-sm rounded-circle">
                                </div>
                                <div>
                                    <h5 class="mb-1">{{ $project->name }}</h5>
                                    <p class="mb-1">Project Id : <span
                                            class="text-dark fw-medium">{{ $project->project_code }}</span></p>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-sm badge-soft-danger fw-medium me-2 border-0"><i
                                                class="ti ti-arrow-up-right me-1"></i>{{ ucfirst($project->priority) }}</span>
                                        <span
                                            class="badge badge-sm bg-success">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="py-1 px-2 fs-12 bg-soft-danger rounded text-danger fw-medium"><i
                                        class="ti ti-building-estate me-1"></i>{{ $project->type }}</span>
                                <span
                                    class="btn btn-xs btn-success fs-12 py-1 px-2 fw-medium d-inline-flex align-items-center"><i
                                        class="ti ti-thumb-up me-1"></i>{{ $project->progress }}% Complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <h6 class="mb-3 fw-semibold">Project Information</h6>
                        <div class="border-bottom mb-3 pb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <p class="mb-0">Start Date</p>
                                <p class="mb-0 text-dark">{{ optional($project->start_date)->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <p class="mb-0">Due Date</p>
                                <p class="mb-0 text-dark">{{ optional($project->end_date)->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <p class="mb-0">Deal Value</p>
                                <p class="mb-0 text-dark">₹{{ number_format($project->budget, 2) }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <p class="mb-0">Spent</p>
                                <p class="mb-0 text-dark">₹{{ number_format($project->spent, 2) }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <p class="mb-0">Project Type</p>
                                <p class="mb-0 text-dark">{{ $project->type }}</p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <p class="mb-0">Location</p>
                                <p class="mb-0 text-dark">{{ $project->location ?: '-' }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <h6 class="mb-3 fw-semibold">Client</h6>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-xs rounded-circle me-2">
                                    <img src="{{ asset('assets/img/icons/company-icon-08.svg') }}" alt="Img"
                                        class="img-fluid rounded-circle w-auto h-auto">
                                </span>
                                <div>
                                    <p class="mb-0">{{ $project->client?->name ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <h6 class="mb-3 fw-semibold">Responsible Persons</h6>
                        </div>
                        <div class="mb-3">
                            <div class="avatar-list-stacked avatar-group-sm">
                                @foreach ($project->tasks->pluck('employee')->filter()->unique('id')->take(5) as $employee)
                                    <span class="avatar avatar-rounded">
                                        <img class="border border-white"
                                            src="{{ asset($employee->avatar ?: 'assets/img/profiles/avatar-01.jpg') }}" alt="img">
                                    </span>
                                @endforeach
                                @if ($project->tasks->pluck('employee')->filter()->count() === 0)
                                    <a class="avatar bg-light avatar-rounded text-dark" href="javascript:void(0);">0</a>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <h6 class="mb-3 fw-semibold">Team Leader</h6>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-xs rounded-circle me-2">
                                    <img src="{{ asset($project->manager?->avatar ?: 'assets/img/users/avatar-4.jpg') }}"
                                        alt="Img" class="img-fluid rounded-circle w-auto h-auto">
                                </span>
                                <div>
                                    <p class="mb-0">{{ $project->manager?->name ?? 'Not assigned' }}</p>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 fw-semibold">Task Summary</h6>
                            <p class="mb-0 fw-medium text-dark"><i class="ti ti-subtask me-1"></i>{{ $project->tasks_count }}
                                Tasks</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <p class="mb-0">Completed</p>
                            <p class="mb-0 text-dark">{{ $project->completed_tasks_count }}</p>
                        </div>
                        <hr>
                        <div class="d-flex align-items-center justify-content-between mb-0">
                            <p class="mb-0">Pending</p>
                            <p class="mb-0 text-dark">{{ $project->tasks_count - $project->completed_tasks_count }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="mb-3 pb-3 border-bottom">
                    <h5 class="mb-3">Project Pipeline Status</h5>
                    <div class="step-progress d-flex flex-wrap gap-2">
                        <div class="step bg-indigo">Planning</div>
                        <div class="step bg-cyan">Execution</div>
                        <div class="step bg-success">Inspection</div>
                        <div class="step bg-orange">Completed</div>
                        <div class="step bg-transparent"></div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body pb-0 pt-2 px-2">
                        <ul class="nav nav-tabs nav-bordered border-0 mb-0" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tab_1" data-bs-toggle="tab" class="nav-link active border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-alarm-minus me-1"></i>Tasks</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab_2" data-bs-toggle="tab" class="nav-link border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-report-money me-1"></i>Payments</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab_3" data-bs-toggle="tab" class="nav-link border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-file me-1"></i>Expenses</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab_4" data-bs-toggle="tab" class="nav-link border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-receipt-dollar me-1"></i>Quotation &
                                        Billing</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab_5" data-bs-toggle="tab" class="nav-link border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-list-numbers me-1"></i>Payment Stages</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab_6" data-bs-toggle="tab" class="nav-link border-3" role="tab">
                                    <span class="d-md-inline-block"><i class="ti ti-git-branch me-1"></i>Variations</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="tab-content pt-0">
                    <div class="tab-pane active show" id="tab_1">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                <h5 class="fw-semibold mb-0">Tasks</h5>
                                <a href="{{ route('tasks.index', ['project_id' => $project->id]) }}"
                                    class="link-primary fw-medium"><i class="ti ti-circle-plus me-1"></i>Manage Tasks</a>
                            </div>
                            <div class="card-body">
                                @forelse ($project->tasks as $task)
                                    <div class="card border shadow-none mb-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                                <div>
                                                    <h6 class="fw-medium fs-14 mb-1">{{ $task->title }}</h6>
                                                    <p class="mb-1">{{ $task->description ?: 'Task planned for this project.' }}</p>
                                                    <p class="mb-0">Assigned to {{ $task->employee?->name ?? 'Unassigned' }} | Due
                                                        {{ optional($task->due_date)->format('d M Y') ?? '-' }}
                                                    </p>
                                                </div>
                                                <span
                                                    class="badge badge-soft-warning">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No tasks linked to this project yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_2">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                <h5 class="fw-semibold mb-0">Payments</h5>
                                <a href="{{ route('payments.index') }}" class="link-primary fw-medium"><i
                                        class="ti ti-circle-plus me-1"></i>View All Payments</a>
                            </div>
                            <div class="card-body">
                                @forelse ($project->payments as $payment)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-sm-flex align-items-center justify-content-between pb-2">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="avatar avatar-md me-2 flex-shrink-0">
                                                        <img src="{{ asset('assets/img/profiles/avatar-19.jpg') }}" alt="img">
                                                    </span>
                                                    <p class="mb-0"><span
                                                            class="text-dark fw-medium">{{ $payment->quotation?->quotation_number ?? $payment->id }}</span>
                                                        for {{ $payment->client?->name ?? 'client' }}</p>
                                                </div>
                                                <div class="d-inline-flex align-items-center mb-2">
                                                    <span
                                                        class="btn btn-sm btn-outline-light">{{ ucfirst($payment->status) }}</span>
                                                </div>
                                            </div>
                                            <p class="mb-0">Amount: ₹{{ number_format($payment->amount, 2) }} | Method:
                                                {{ $payment->payment_method }} | Due
                                                {{ optional($payment->due_date)->format('d M Y') ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No payments linked to this project yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header">
                                <h5 class="fw-semibold mb-0">Expenses</h5>
                            </div>
                            <div class="card-body">
                                @forelse ($project->expenses as $expense)
                                    @php
                                        $expenseTitle = $expense->description ?: ($expense->mainCategory?->name ?? 'Expense');
                                        $expenseCategory = collect([
                                            $expense->mainCategory?->name,
                                            $expense->category?->name,
                                        ])->filter()->implode(' - ') ?: 'General';
                                    @endphp
                                    <div class="card border shadow-none mb-3">
                                        <div class="card-body pb-0">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <div class="mb-3">
                                                        <h6 class="fw-semibold fs-14 mb-1">{{ $expenseTitle }}</h6>
                                                        <p>{{ $expenseCategory }} expense logged by
                                                            {{ $expense->employee?->name ?? 'team' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 text-md-end">
                                                    <div class="mb-3 d-inline-flex align-items-center">
                                                        <span
                                                            class="badge badge-purple-light me-1">Rs {{ number_format($expense->amount, 2) }}</span>
                                                        <span
                                                            class="badge bg-success me-1">{{ optional($expense->expense_date)->format('d M Y') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No expenses linked to this project yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- QUOTATION & BILLING TAB --}}
                    <div class="tab-pane fade" id="tab_4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                <h5 class="fw-semibold mb-0">Quotation & Billing</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('projects.final-bill', $project) }}"
                                        class="btn btn-sm btn-outline-primary" id="refresh-bill">Refresh Bill</a>
                                    <a href="{{ route('projects.invoice', $project) }}" class="btn btn-sm btn-success"
                                        target="_blank">
                                        <i class="ti ti-file-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($latestQuotation = $project->quotations()->latest()->first())
                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-2">Latest Quotation #{{ $latestQuotation->id }}</h6>
                                        @forelse($latestQuotation->items as $item)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>{{ $item->description }}</span>
                                                <span>₹{{ number_format($item->amount, 2) }}</span>
                                            </div>
                                        @empty
                                            <p class="text-muted">No items in this quotation.</p>
                                        @endforelse
                                        <div class="border-top pt-2 mt-2">
                                            <div class="d-flex justify-content-between fw-bold">
                                                <span>Quotation Total:</span>
                                                <span>₹{{ number_format($latestQuotation->total_amount, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-3">No quotation created yet.</p>
                                @endif

                                {{-- FINAL BILL SUMMARY --}}
                                <div class="card border shadow-sm">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3">Final Bill Amount</h6>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="fw-semibold text-primary fs-20">
                                                    ₹{{ number_format($project->final_bill, 2) }}</div>
                                                <small class="text-muted">Final Bill</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PAYMENT STAGES TAB --}}
                    <div class="tab-pane fade" id="tab_5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                <h5 class="fw-semibold mb-0">Payment Stages</h5>
                                <a href="{{ route('payment-stages.index', ['project_id' => $project->id]) }}"
                                    class="link-primary fw-medium">
                                    <i class="ti ti-eye me-1"></i>Manage Stages
                                </a>
                            </div>
                            <div class="card-body">
                                @forelse ($project->paymentStages as $stage)
                                    <div class="card border shadow-none mb-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h6 class="fw-medium mb-1">{{ $stage->stage_name }}</h6>
                                                    <p class="mb-1">{{ $stage->percentage }}% -
                                                        ₹{{ number_format($stage->amount ?? 0, 2) }}</p>
                                                </div>
                                                <span class="badge bg-{{ $stage->status === 'paid' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($stage->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No payment stages defined for this project.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- VARIATIONS TAB --}}
                    <div class="tab-pane fade" id="tab_6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                <h5 class="fw-semibold mb-0">Variations</h5>
                                <a href="{{ route('variations.index', ['project_id' => $project->id]) }}"
                                    class="link-primary fw-medium">
                                    <i class="ti ti-git-branch me-1"></i>Manage Variations
                                </a>
                            </div>
                            <div class="card-body">
                                @forelse ($project->variations as $variation)
                                    <div class="card border shadow-none mb-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                                <div>
                                                    <h6 class="fw-medium mb-1">{{ ucfirst($variation->type) }}</h6>
                                                    <p class="mb-1">{{ Str::limit($variation->description, 80) }}</p>
                                                    <p class="mb-0">₹{{ number_format($variation->amount, 2) }} |
                                                        {{ $variation->date->format('d M Y') }}
                                                    </p>
                                                </div>
                                                <span
                                                    class="badge bg-{{ $variation->status === 'approved' ? 'success' : ($variation->status === 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($variation->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No variations recorded for this project.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade" id="edit_project_detail" tabindex="-1" aria-hidden="true">
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
                        <input type="hidden" name="client_id" value="{{ $project->client_id }}">
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
                            <label class="form-label">Manager</label>
                            <select name="manager_id" class="form-select">
                                <option value="">Select</option>
                                @foreach ($availableEmployees as $employee)
                                    <option value="{{ $employee->id }}" @selected($employee->id === $project->manager_id)>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach (['planning', 'active', 'on_hold', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected($status === $project->status)>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                @foreach (['low', 'medium', 'high'] as $priority)
                                    <option value="{{ $priority }}" @selected($priority === $project->priority)>
                                        {{ ucfirst($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <input type="text" name="type" class="form-control" value="{{ $project->type }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Progress</label>
                            <input type="number" name="progress" class="form-control" min="0" max="100"
                                value="{{ $project->progress }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Budget</label>
                            <input type="number" step="0.01" name="budget" class="form-control"
                                value="{{ $project->budget }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Spent</label>
                            <input type="number" step="0.01" name="spent" class="form-control"
                                value="{{ $project->spent }}">
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
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
@endpush

