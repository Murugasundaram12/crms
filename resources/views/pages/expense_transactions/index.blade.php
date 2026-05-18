@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Expenses</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Expenses</li>
                </ol>
            </nav>
        </div>

        <div class="gap-2 d-flex align-items-center flex-wrap">
            <form action="{{ route('expense-transactions.index') }}" method="GET"
                class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                </div>
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
        @forelse ($expenseTransactions as $tx)
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-tag badge-soft-info">{{ $tx->payment_mode }}</span>
                            </div>
                            <div class="dropdown">
                                <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                    data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @can('expenses-edit')
                                        <a class="dropdown-item" href="{{ route('expense-transactions.edit', $tx) }}">
                                            <i class="ti ti-edit text-blue"></i> Edit
                                        </a>
                                    @endcan

                                    @can('expenses-delete')
                                        <form method="POST" action="{{ route('expense-transactions.destroy', $tx) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item"
                                                onclick="return confirm('Delete this expense?')">
                                                <i class="ti ti-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-medium">{{ $tx->description ?: 'Expense' }}</h6>
                        <p class="text-muted mb-3">
                            {{ $tx->mainCategory?->name }} - {{ $tx->category?->name }}
                            @if($tx->project) <br>Project: {{ $tx->project->name }} @endif
                        </p>

                        <div class="d-flex justify-content-between align-items-end gap-2">
                            <span class="h6 mb-0">₹{{ number_format($tx->paid_amount, 2) }}</span>
                            <span class="badge bg-light text-dark">
                                {{ \Carbon\Carbon::parse($tx->current_date)->format('d/m/Y') }}
                                <br>
                                <small class="text-muted">{{ $tx->current_time }}</small>
                            </span>
                        </div>

                        @if($tx->image_path)
                            <div class="mt-3">
                                <a href="{{ asset('storage/' . $tx->image_path) }}" target="_blank"
                                    class="text-decoration-none small">
                                    View image
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">No expenses recorded</h5>
                        <p class="text-muted mb-3">Add your first expense.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $expenseTransactions->links() }}

    {{-- Add Offcanvas --}}
    <div class="offcanvas offcanvas-end" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Expense</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('expense-transactions.store') }}" method="POST" enctype="multipart/form-data"
                class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label">Main Category <span class="text-danger">*</span></label>
                    <select name="main_category_id" class="form-select" required>
                        <option value="">Select</option>
                        @foreach(\App\Models\MainCategory::query()->where('status', true)->orderBy('name')->get() as $mc)
                            <option value="{{ $mc->id }}" @selected(old('main_category_id') == $mc->id)>{{ $mc->name }}</option>
                        @endforeach
                    </select>
                    @error('main_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select</option>
                        @foreach(\App\Models\Category::query()->orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">None</option>
                        @foreach(\App\Models\Project::query()->orderBy('name')->get() as $p)
                            <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Paid Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="paid_amount" class="form-control"
                        value="{{ old('paid_amount') }}" required>
                    @error('paid_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" class="form-select" required>
                        <option value="">Select</option>
                        @foreach(['Cash', 'HDFC', 'SBI', 'Gpay', 'PhonePe', 'KVBL', 'Kotak Mahindra', 'TMB', 'Equitas'] as $pm)
                            <option value="{{ $pm }}" @selected(old('payment_mode') === $pm)>{{ $pm }}</option>
                        @endforeach
                    </select>
                    @error('payment_mode')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    @php $today = \Carbon\Carbon::now()->format('d/m/Y'); @endphp
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="text" name="current_date" class="form-control" value="{{ old('current_date', $today) }}"
                        required>
                    <div class="form-text">Format: dd/mm/yyyy</div>
                    @error('current_date')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    @php $now = \Carbon\Carbon::now()->format('h:i:s A'); @endphp
                    <label class="form-label">Time <span class="text-danger">*</span></label>
                    <input type="text" name="current_time" class="form-control" value="{{ old('current_time', $now) }}"
                        required>
                    <div class="form-text">Example: 01:14:20 AM</div>
                    @error('current_time')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
@endsection