@extends('layouts.app')

@section('title', 'Expenses History')

@section('content')
    @include('partials.alerts')

    @php
        $filterRoute = request()->routeIs('expenses.unpaid-history')
            ? 'expenses.unpaid-history'
            : (request()->routeIs('expenses.deleted-history') ? 'expenses.deleted-history' : 'expenses.history');
    @endphp

    <div class="mb-3">
        <form method="GET" action="{{ route($filterRoute) }}" class="row g-2 align-items-end">
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
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filter</button>
                <a href="{{ route($filterRoute) }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-tabs-solid nav-justified mb-0">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('expenses.history') }}">
                        <i class="ti ti-file-invoice me-1"></i>Other Expenses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('labour-expenses.history') }}">
                        <i class="ti ti-user-cog me-1"></i>Labour
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('vendor-expenses.history') }}">
                        <i class="ti ti-building-warehouse me-1"></i>Vendor
                    </a>
                </li>
            </ul>

            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-nowrap mb-0">
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
                                <th>Action</th>
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
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="javascript:void(0);" class="text-success" title="Edit">
                                                <i class="ti ti-edit fs-16"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-link p-0 text-danger expense-delete-trigger"
                                                title="Delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#expenseDeleteModal"
                                                data-expense-id="{{ $expense->id }}"
                                                data-expense-title="{{ $expense->mainCategory?->name ?? 'Expense' }}"
                                                data-expense-amount="{{ number_format((float) $expense->amount, 2) }}">
                                                <i class="ti ti-trash fs-16"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-4">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
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

    <div class="d-flex justify-content-end mt-3 flex-wrap gap-4 fw-semibold">
        <div>Total Amount: <span class="text-warning">{{ number_format((float) ($totals->total_amount ?? 0), 2) }}</span></div>
        <div>Total Paid Amount: <span class="text-success">{{ number_format((float) ($totals->total_paid_amount ?? 0), 2) }}</span></div>
        <div>Total Unpaid Amount: <span class="text-danger">{{ number_format((float) ($totals->total_unpaid_amount ?? 0), 2) }}</span></div>
        <div>Total Advanced Amount: <span class="text-info">{{ number_format((float) ($totals->total_advanced_amount ?? 0), 2) }}</span></div>
    </div>

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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const deleteId = document.getElementById('expenseDeleteId');
                const deleteSummary = document.getElementById('expenseDeleteSummary');

                document.querySelectorAll('.expense-delete-trigger').forEach(function (button) {
                    button.addEventListener('click', function () {
                        deleteId.value = button.dataset.expenseId || '';
                        deleteSummary.textContent = `${button.dataset.expenseTitle || 'Expense'} - Rs. ${button.dataset.expenseAmount || '0.00'}`;
                    });
                });
            });
        </script>
    @endpush
@endsection
