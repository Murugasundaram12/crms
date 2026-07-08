@extends('layouts.app')
@section('title', 'Labour Expenses History')
@section('content')
    @include('partials.alerts')
    @php
        $editingTransaction = $editingTransaction ?? null;
        $isCreatingTransaction = request()->boolean('create');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Labour Expenses</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Expenses</li>
                </ol>
            </nav>
        </div>
        @can('expenses-create')
            <a href="{{ route('labour-expenses.create.legacy') }}" class="btn btn-primary">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Labour Expense
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Total Amount</p><h5 class="mb-0 text-warning">Rs. {{ number_format((float) ($totals->total_amount ?? 0), 2) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Paid</p><h5 class="mb-0 text-success">Rs. {{ number_format((float) ($totals->total_paid_amount ?? 0), 2) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Unpaid</p><h5 class="mb-0 text-danger">Rs. {{ number_format((float) ($totals->total_unpaid_amount ?? 0), 2) }}</h5></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Advanced</p><h5 class="mb-0 text-info">Rs. {{ number_format((float) ($totals->total_advanced_amount ?? 0), 2) }}</h5></div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
        <form method="GET" action="{{ route('labour-expenses.history') }}" class="row g-3 align-items-end m-0">
            <div class="col-md-2">
                <label class="form-label mb-1">Main Category</label>
                <select name="main_category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory->id }}" @selected((string) request('main_category_id') === (string) $mainCategory->id)>{{ $mainCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Project Name</label>
                <select name="project_id" class="form-select">
                    <option value="">All</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Labour Name</label>
                <select name="labour_id" class="form-select">
                    <option value="">All</option>
                    @foreach($labours as $labour)
                        <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id)>{{ $labour->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Text" value="{{ request('q') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                <a href="{{ route('labour-expenses.history') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
            </div>
        </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-3">
                @php
                    $pageTransactions = $transactions->getCollection();
                    $pageTotals = [
                        'amount' => $pageTransactions->sum(fn($tx) => (float) $tx->amount),
                        'paid' => $pageTransactions->sum(fn($tx) => (float) $tx->paid_amount),
                        'unpaid' => $pageTransactions->sum(fn($tx) => (float) $tx->unpaid_amount),
                        'advance' => $pageTransactions->sum(fn($tx) => (float) $tx->extra_amount),
                    ];
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Paid Date</th>
                                <th>Main Category</th>
                                <th>Category Name</th>
                                <th>Project Name</th>
                                <th>Labour Name</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Unpaid</th>
                                <th>Advanced Amount</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Payment Mode</th>
                                <th>Added By</th>
                                <th>Edited By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td>{{ optional($tx->current_date)->format('d-m-Y') }}<br><small>{{ $tx->current_time ?? '--' }}</small></td>
                                    <td>{{ $tx->mainCategory?->name ?? '-' }}</td>
                                    <td>{{ $tx->category?->name ?? '-' }}</td>
                                    <td>{{ $tx->project?->name ?? '-' }}</td>
                                    <td>{{ $tx->labour?->name ?? '-' }}</td>
                                    <td class="text-warning fw-semibold">{{ number_format((float) $tx->amount, 2) }}</td>
                                    <td class="text-success fw-semibold">{{ number_format((float) $tx->paid_amount, 2) }}</td>
                                    <td class="text-danger fw-semibold">{{ number_format((float) $tx->unpaid_amount, 2) }}</td>
                                    <td class="text-info fw-semibold">{{ number_format((float) $tx->extra_amount, 2) }}</td>
                                    <td>{{ $tx->description ?? '--' }}</td>
                                    <td>{{ $tx->image ?: '--' }}</td>
                                    <td>{{ $tx->payment_mode_label ?? '--' }}</td>
                                    <td>{{ $tx->user?->name ?? '--' }}</td>
                                    <td>{{ $tx->editedByUser?->name ?? '--' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ route('labour-expenses.edit.legacy', $tx->id) }}" class="btn btn-icon btn-sm btn-outline-success" title="Edit">
                                                <i class="ti ti-edit fs-16"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-icon btn-sm btn-outline-danger labour-expense-delete-trigger"
                                                title="Delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#labourExpenseDeleteModal"
                                                data-expense-id="{{ $tx->id }}"
                                                data-expense-title="{{ $tx->labour?->name ?? 'Labour Expense' }}"
                                                data-expense-amount="{{ number_format((float) $tx->amount, 2) }}">
                                                <i class="ti ti-trash fs-16"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="text-center py-4">No records found</td></tr>
                            @endforelse
                        </tbody>
                        @if($pageTransactions->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">This Page Total</th>
                                    <th class="text-warning">Rs. {{ number_format($pageTotals['amount'], 2) }}</th>
                                    <th class="text-success">Rs. {{ number_format($pageTotals['paid'], 2) }}</th>
                                    <th class="text-danger">Rs. {{ number_format($pageTotals['unpaid'], 2) }}</th>
                                    <th class="text-info">Rs. {{ number_format($pageTotals['advance'], 2) }}</th>
                                    <th colspan="6"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <p class="mb-0 text-muted">Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} results</p>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="labourExpenseDeleteModal" tabindex="-1" aria-labelledby="labourExpenseDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('labour-expenses.delete-record') }}" class="modal-content border-0 shadow">
                @csrf
                <input type="hidden" name="id" id="labourExpenseDeleteId">

                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title" id="labourExpenseDeleteModalLabel">Delete Labour Expense</h5>
                        <p class="mb-0 text-muted small">This labour expense will move to deleted history.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="avatar avatar-md rounded-circle bg-danger-transparent text-danger d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-trash fs-20"></i>
                        </span>
                        <div>
                            <p class="mb-1 fw-semibold">Are you sure you want to delete this labour expense?</p>
                            <p class="mb-0 text-muted small" id="labourExpenseDeleteSummary">Selected labour expense</p>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="labourExpenseDeleteReason" class="form-label">Reason</label>
                        <input type="text" name="delete_reason" id="labourExpenseDeleteReason" class="form-control"
                            value="Deleted from list" placeholder="Enter reason" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($isCreatingTransaction || $editingTransaction)
        @php($modalTransaction = $editingTransaction)
        @php($modalAction = $modalTransaction ? route('labour-expenses.update.legacy', $modalTransaction->id) : route('labour-expenses.store'))
        @php($modalTitle = $modalTransaction ? 'Edit Labour Expense' : 'Add Labour Expense')
        <div class="modal fade" id="labourExpenseModal" tabindex="-1" aria-labelledby="labourExpenseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ $modalAction }}" class="modal-content border-0 shadow">
                    @csrf
                    @if($modalTransaction)
                        @method('PUT')
                    @endif

                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="labourExpenseModalLabel">{{ $modalTitle }}</h5>
                            <p class="mb-0 text-muted small">Save labour expense details in the main expenses flow.</p>
                        </div>
                        <a href="{{ route('labour-expenses.history') }}" class="btn-close" aria-label="Close"></a>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Labour</label>
                                <select name="labour_id" class="form-select" required>
                                    <option value="">Select labour</option>
                                    @foreach($labours as $labour)
                                        <option value="{{ $labour->id }}" @selected((int) old('labour_id', $modalTransaction?->labour_id) === (int) $labour->id)>{{ $labour->name }}</option>
                                    @endforeach
                                </select>
                                @error('labour_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Main Category</label>
                                <select name="main_category_id" class="form-select">
                                    <option value="">Select main category</option>
                                    @foreach($mainCategories as $mainCategory)
                                        <option value="{{ $mainCategory->id }}" @selected((int) old('main_category_id', $modalTransaction?->main_category_id) === (int) $mainCategory->id)>{{ $mainCategory->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $modalTransaction?->category_id) === (int) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Project Name</label>
                                <select name="project_id" class="form-select">
                                    <option value="">Select project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" @selected((int) old('project_id', $modalTransaction?->project_id) === (int) $project->id)>{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" min="0" required value="{{ old('amount', $modalTransaction?->amount) }}">
                                @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" name="paid_amount" class="form-control" min="0" required value="{{ old('paid_amount', $modalTransaction?->paid_amt ?? 0) }}">
                                @error('paid_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Mode</label>
                                <select name="payment_mode" class="form-select">
                                    <option value="">Select payment mode</option>
                                    @foreach($paymentModes as $modeId => $modeLabel)
                                        <option value="{{ $modeId }}" @selected((string) old('payment_mode', $modalTransaction?->payment_mode) === (string) $modeId)>{{ $modeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Date</label>
                                <input type="date" name="current_date" class="form-control" value="{{ old('current_date', optional($modalTransaction?->current_date)->format('Y-m-d') ?? now()->toDateString()) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Image</label>
                                <input type="text" name="image" class="form-control" maxlength="250" value="{{ old('image', $modalTransaction?->image) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $modalTransaction?->description) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('labour-expenses.history') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modalElement = document.getElementById('labourExpenseModal');
                    if (modalElement && window.bootstrap) {
                        new bootstrap.Modal(modalElement).show();
                    }
                });
            </script>
        @endpush
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const deleteId = document.getElementById('labourExpenseDeleteId');
                const deleteSummary = document.getElementById('labourExpenseDeleteSummary');

                document.querySelectorAll('.labour-expense-delete-trigger').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (!deleteId || !deleteSummary) {
                            return;
                        }

                        deleteId.value = button.dataset.expenseId || '';
                        deleteSummary.textContent = `${button.dataset.expenseTitle || 'Labour Expense'} - Rs. ${button.dataset.expenseAmount || '0.00'}`;
                    });
                });
            });
        </script>
    @endpush
@endsection
