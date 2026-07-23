@extends('layouts.app')

@section('title', 'Expenses History')

@section('content')
    @include('partials.alerts')

    @php
        $filterRoute = request()->routeIs('expenses.unpaid-history')
            ? 'expenses.unpaid-history'
            : (request()->routeIs('expenses.deleted-history') ? 'expenses.deleted-history' : 'expenses.history');
        $editingExpense = $editingExpense ?? null;
        $isUnpaidHistory = request()->routeIs('expenses.unpaid-history');
        $isDeletedHistory = request()->routeIs('expenses.deleted-history');
        $isCreatingExpense = request()->boolean('create') && ! $isUnpaidHistory && ! $isDeletedHistory;
        $pageTitle = $isDeletedHistory ? 'Deleted Expenses' : ($isUnpaidHistory ? 'Unpaid Expenses' : 'Expenses History');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ $pageTitle }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
        @if(! $isUnpaidHistory && ! $isDeletedHistory)
            @can('expenses-create')
                <a href="{{ route('expenses.create.legacy') }}" class="btn btn-primary">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Expense
                </a>
            @endcan
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Total Amount</p><h5 class="mb-0 text-warning">Rs. {{ number_format((float) ($totals->total_amount ?? 0), 2) }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Paid</p><h5 class="mb-0 text-success">Rs. {{ number_format((float) ($totals->total_paid_amount ?? 0), 2) }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Unpaid</p><h5 class="mb-0 text-danger">Rs. {{ number_format((float) ($totals->total_unpaid_amount ?? 0), 2) }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body"><p class="text-muted mb-1">Advanced</p><h5 class="mb-0 text-info">Rs. {{ number_format((float) ($totals->total_advanced_amount ?? 0), 2) }}</h5></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
        <form method="GET" action="{{ route($filterRoute) }}" class="row g-3 align-items-end m-0">
            <div class="col-md-2">
                <label class="form-label mb-1">Main Category</label>
                <select name="main_category" class="form-select">
                    <option value="">All</option>
                    @foreach($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory }}" @selected(request('main_category') == $mainCategory)>{{ strtoupper($mainCategory) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Category</label>
                <select name="category_name" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category_name') == $category)>{{ $category }}</option>
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
                <label class="form-label mb-1">Member Name</label>
                <select name="member_id" class="form-select">
                    <option value="">All</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('member_id') === (string) $employee->id)>{{ $employee->name }}</option>
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
                <a href="{{ route($filterRoute) }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
            </div>
        </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-3">
                @php
                    $pageExpenses = $expenses->getCollection();
                    $pageTotals = [
                        'amount' => $pageExpenses->sum(fn($expense) => (float) $expense->amount),
                        'paid' => $pageExpenses->sum(fn($expense) => (float) $expense->paid_amount),
                        'unpaid' => $pageExpenses->sum(fn($expense) => (float) $expense->unpaid_amount),
                        'advance' => $pageExpenses->sum(fn($expense) => (float) $expense->extra_amount),
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
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Unpaid</th>
                                <th>Advanced Amount</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Payment Mode</th>
                                <th>Added By</th>
                                <th>Edited By</th>
                                @if($isDeletedHistory)
                                    <th>Delete Reason</th>
                                @endif
                                @unless($isDeletedHistory)
                                    <th>Action</th>
                                @endunless
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td>
                                        {{ optional($expense->expense_date)->format('d-m-Y') }}
                                        <br>
                                        <small>{{ optional($expense->created_at)->format('h:i A') }}</small>
                                    </td>
                                    <td>{{ $expense->mainCategory?->name ?? '-' }}</td>
                                    <td>{{ $expense->category?->name ?? '-' }}</td>
                                    <td>{{ $expense->project?->name ?? '-' }}</td>
                                    <td class="text-warning fw-semibold">{{ number_format((float) $expense->amount, 2) }}</td>
                                    <td class="text-success fw-semibold">{{ number_format((float) $expense->paid_amount, 2) }}</td>
                                    <td class="text-danger fw-semibold">{{ number_format((float) $expense->unpaid_amount, 2) }}</td>
                                    <td class="text-info fw-semibold">{{ number_format((float) $expense->extra_amount, 2) }}</td>
                                    <td>{{ $expense->description ?? '-' }}</td>
                                    <td>--</td>
                                    <td>{{ $expense->payment_mode_label ?? '--' }}</td>
                                    <td>{{ $expense->employee?->name ?? '--' }}</td>
                                    <td>{{ $expense->editedByUser?->name ?? '--' }}</td>
                                    @if($isDeletedHistory)
                                        <td>{{ $expense->delete_reason ?: '-' }}</td>
                                    @else
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($isUnpaidHistory)
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm btn-outline-primary expense-settle-trigger"
                                                        title="Settle unpaid"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#expenseSettleModal"
                                                        data-expense-id="{{ $expense->id }}"
                                                        data-expense-title="{{ $expense->mainCategory?->name ?? 'Expense' }}"
                                                        data-expense-unpaid="{{ (int) $expense->unpaid_amt }}"
                                                        data-expense-unpaid-formatted="{{ number_format((float) $expense->unpaid_amt, 2) }}">
                                                        <i class="ti ti-cash-banknote fs-16"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ route('expenses.edit.legacy', $expense->id) }}" class="btn btn-icon btn-sm btn-outline-success" title="Edit">
                                                        <i class="ti ti-edit fs-16"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm btn-outline-danger expense-delete-trigger"
                                                        title="Delete"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#expenseDeleteModal"
                                                        data-expense-id="{{ $expense->id }}"
                                                        data-expense-title="{{ $expense->mainCategory?->name ?? 'Expense' }}"
                                                        data-expense-amount="{{ number_format((float) $expense->amount, 2) }}">
                                                        <i class="ti ti-trash fs-16"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-4">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($pageExpenses->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">This Page Total</th>
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
                    <p class="mb-0 text-muted">
                        Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of
                        {{ $expenses->total() }} results
                    </p>
                    {{ $expenses->links() }}
                </div>
            </div>
        </div>
    </div>

    @unless($isDeletedHistory || $isUnpaidHistory)
        <div class="modal fade" id="expenseDeleteModal" tabindex="-1" aria-labelledby="expenseDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('expenses.delete-record') }}" class="modal-content border-0 shadow">
                    @csrf
                    <input type="hidden" name="expense_id" id="expenseDeleteId">

                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="expenseDeleteModalLabel">Delete Expense</h5>
                            <p class="mb-0 text-muted small">This expense will move to deleted history.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="avatar avatar-md rounded-circle bg-danger-transparent text-danger d-inline-flex align-items-center justify-content-center">
                                <i class="ti ti-trash fs-20"></i>
                            </span>
                            <div>
                                <p class="mb-1 fw-semibold">Are you sure you want to delete this expense?</p>
                                <p class="mb-0 text-muted small" id="expenseDeleteSummary">Selected expense</p>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="expenseDeleteReason" class="form-label">Reason</label>
                            <input type="text" name="delete_reason" id="expenseDeleteReason" class="form-control"
                                placeholder="Enter reason">
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
    @endunless

    @if($isUnpaidHistory)
        <div class="modal fade" id="expenseSettleModal" tabindex="-1" aria-labelledby="expenseSettleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('expenses.unpaid-store') }}" class="modal-content border-0 shadow">
                    @csrf
                    <input type="hidden" name="expense_id" id="expenseSettleId">

                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="expenseSettleModalLabel">Settle Unpaid Expense</h5>
                            <p class="mb-0 text-muted small" id="expenseSettleSummary">Selected expense</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="expenseSettleAmount" class="form-label">Paid Amount</label>
                            <input type="number" name="paid_amount" id="expenseSettleAmount" class="form-control" min="1" required>
                        </div>

                        <div class="mb-0">
                            <label for="expenseSettleNotes" class="form-label">Notes</label>
                            <textarea name="notes" id="expenseSettleNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($isCreatingExpense)
        <div class="modal fade" id="expenseCreateModal" tabindex="-1" aria-labelledby="expenseCreateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('expenses.store.new') }}" class="modal-content border-0 shadow">
                    @csrf

                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="expenseCreateModalLabel">Add Expense</h5>
                            <p class="mb-0 text-muted small">Create an expense in the main expenses list.</p>
                        </div>
                        <a href="{{ route('expenses.history') }}" class="btn-close" aria-label="Close"></a>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Main Category</label>
                                <select name="main_category_id" class="form-select">
                                    <option value="">Select main category</option>
                                    @foreach($mainCategoryOptions as $mainCategory)
                                        <option value="{{ $mainCategory->id }}" @selected((int) old('main_category_id') === (int) $mainCategory->id)>
                                            {{ $mainCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('main_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($categoryOptions as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id') === (int) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Project Name</label>
                                <select name="project_id" class="form-select">
                                    <option value="">Select project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" @selected((int) old('project_id') === (int) $project->id)>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" min="0" required
                                    value="{{ old('amount') }}">
                                @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" name="paid_amt" class="form-control" min="0" required
                                    value="{{ old('paid_amt', 0) }}">
                                @error('paid_amt')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method_id" class="form-select">
                                    <option value="">Select Payment Method</option>
                                    @foreach($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((string) old('payment_method_id') === (string) $paymentMethod->id)>
                                            {{ $paymentMethod->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Date</label>
                                <input type="date" name="current_date" class="form-control" required
                                    value="{{ old('current_date', now()->toDateString()) }}">
                                @error('current_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Image</label>
                                <input type="text" name="image" class="form-control" maxlength="250"
                                    value="{{ old('image') }}" placeholder="Image filename or path">
                                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('expenses.history') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($editingExpense)
        <div class="modal fade" id="expenseEditModal" tabindex="-1" aria-labelledby="expenseEditModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <form method="POST" action="{{ route('expenses.update.new', $editingExpense->id) }}" class="modal-content border-0 shadow">
                    @csrf
                    @method('PUT')

                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="expenseEditModalLabel">Edit Expense</h5>
                            <p class="mb-0 text-muted small">Update expense details and paid amount.</p>
                        </div>
                        <a href="{{ route('expenses.history') }}" class="btn-close" aria-label="Close"></a>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Main Category</label>
                                <select name="main_category_id" class="form-select">
                                    <option value="">Select main category</option>
                                    @foreach($mainCategoryOptions as $mainCategory)
                                        <option value="{{ $mainCategory->id }}" @selected((int) old('main_category_id', $editingExpense->main_category_id) === (int) $mainCategory->id)>
                                            {{ $mainCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('main_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($categoryOptions as $category)
                                        <option value="{{ $category->id }}" @selected((int) old('category_id', $editingExpense->category_id) === (int) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Project Name</label>
                                <select name="project_id" class="form-select">
                                    <option value="">Select project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" @selected((int) old('project_id', $editingExpense->project_id) === (int) $project->id)>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" class="form-control" min="0" required
                                    value="{{ old('amount', $editingExpense->amount) }}">
                                @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Amount</label>
                                <input type="number" name="paid_amt" class="form-control" min="0" required
                                    value="{{ old('paid_amt', $editingExpense->paid_amt) }}">
                                @error('paid_amt')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method_id" class="form-select">
                                    <option value="">Select Payment Method</option>
                                    @foreach($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" @selected((string) old('payment_method_id', $editingExpense->payment_method_id) === (string) $paymentMethod->id)>
                                            {{ $paymentMethod->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method_id')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Paid Date</label>
                                <input type="date" name="current_date" class="form-control" required
                                    value="{{ old('current_date', optional($editingExpense->current_date)->format('Y-m-d')) }}">
                                @error('current_date')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Image</label>
                                <input type="text" name="image" class="form-control" maxlength="250"
                                    value="{{ old('image', $editingExpense->image) }}" placeholder="Image filename or path">
                                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $editingExpense->description) }}</textarea>
                                @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('expenses.history') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const deleteId = document.getElementById('expenseDeleteId');
                const deleteSummary = document.getElementById('expenseDeleteSummary');
                const settleId = document.getElementById('expenseSettleId');
                const settleAmount = document.getElementById('expenseSettleAmount');
                const settleSummary = document.getElementById('expenseSettleSummary');

                document.querySelectorAll('.expense-delete-trigger').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (!deleteId || !deleteSummary) {
                            return;
                        }

                        deleteId.value = button.dataset.expenseId || '';
                        deleteSummary.textContent = `${button.dataset.expenseTitle || 'Expense'} - Rs. ${button.dataset.expenseAmount || '0.00'}`;
                    });
                });

                document.querySelectorAll('.expense-settle-trigger').forEach(function (button) {
                    button.addEventListener('click', function () {
                        if (!settleId || !settleAmount || !settleSummary) {
                            return;
                        }

                        const unpaidAmount = button.dataset.expenseUnpaid || '';

                        settleId.value = button.dataset.expenseId || '';
                        settleAmount.value = unpaidAmount;
                        settleAmount.max = unpaidAmount;
                        settleSummary.textContent = `${button.dataset.expenseTitle || 'Expense'} - Unpaid Rs. ${button.dataset.expenseUnpaidFormatted || '0.00'}`;
                    });
                });

                @if($editingExpense)
                    const editModalElement = document.getElementById('expenseEditModal');
                    if (editModalElement && window.bootstrap) {
                        new bootstrap.Modal(editModalElement).show();
                    }
                @endif

                @if($isCreatingExpense)
                    const createModalElement = document.getElementById('expenseCreateModal');
                    if (createModalElement && window.bootstrap) {
                        new bootstrap.Modal(createModalElement).show();
                    }
                @endif
            });
        </script>
    @endpush
@endsection
