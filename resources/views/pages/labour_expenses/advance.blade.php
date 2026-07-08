@extends('layouts.app')

@section('title', 'Labour Wallet')

@push('styles')
    <style>
        .labour-wallet-scroll {
            max-height: 540px;
            overflow-y: auto;
        }

        .labour-wallet-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #fff;
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Labour Wallet</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour-expenses.history') }}">Labour Expenses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Wallet</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('expenses-edit')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#walletCreditModal">
                    <i class="ti ti-plus me-1"></i>Add Amount
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#walletWithdrawModal">
                    <i class="ti ti-arrow-back-up me-1"></i>Withdraw
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Wallet Balance</p>
                        <h4 class="mb-0 text-info">Rs. {{ number_format((float) $totalWalletBalance, 2) }}</h4>
                    </div>
                    <span class="avatar avatar-md rounded bg-info-transparent text-info d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-wallet fs-22"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Unpaid Labour Expenses</p>
                        <h4 class="mb-0 text-danger">Rs. {{ number_format((float) $totalUnpaidAmount, 2) }}</h4>
                    </div>
                    <span class="avatar avatar-md rounded bg-danger-transparent text-danger d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-alert-circle fs-22"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Labours</p>
                        <h4 class="mb-0">{{ $walletLabours->count() }}</h4>
                    </div>
                    <span class="avatar avatar-md rounded bg-success-transparent text-success d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-users fs-22"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form class="row g-3 align-items-end m-0" method="GET" action="{{ route('labour-expenses.advance-history') }}">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label">Labour</label>
                    <select name="labour_id" class="form-select">
                        <option value="">All Labour</option>
                        @foreach($labours as $labour)
                            <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id)>
                                {{ $labour->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('labour-expenses.advance-history') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Wallet Balances</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive labour-wallet-scroll">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Labour</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th class="text-end">Wallet Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($walletLabours as $labour)
                            <tr>
                                <td>{{ $labour->name }}</td>
                                <td>{{ $labour->phone ?? $labour->phone_number ?? '-' }}</td>
                                <td>{{ $labour->labour_role ?? $labour->labourRole?->name ?? '-' }}</td>
                                <td class="text-end text-info fw-semibold">Rs. {{ number_format((float) $labour->advance_amt, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4">No labour records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Settle Unpaid From Wallet</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive labour-wallet-scroll">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Labour</th>
                            <th>Project</th>
                            <th class="text-end">Unpaid</th>
                            <th class="text-end">Wallet</th>
                            <th>Settle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unpaidExpenses as $expense)
                            @php($settleMax = min((int) $expense->unpaid_amt, (int) ($expense->labour?->advance_amt ?? 0)))
                            <tr>
                                <td>{{ optional($expense->current_date)->format('d-m-Y') }}</td>
                                <td>{{ $expense->labour?->name ?? '-' }}</td>
                                <td>{{ $expense->project?->name ?? '-' }}</td>
                                <td class="text-end text-danger fw-semibold">Rs. {{ number_format((float) $expense->unpaid_amt, 2) }}</td>
                                <td class="text-end text-info fw-semibold">Rs. {{ number_format((float) ($expense->labour?->advance_amt ?? 0), 2) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('labour-expenses.advance-store') }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="entry_type" value="settle">
                                        <input type="hidden" name="labour_id" value="{{ $expense->labour_id }}">
                                        <input type="hidden" name="labour_expense_transaction_id" value="{{ $expense->id }}">
                                        <input type="number" name="amount" class="form-control form-control-sm" min="1"
                                            max="{{ $settleMax }}"
                                            value="{{ $settleMax > 0 ? $settleMax : '' }}" @disabled($settleMax <= 0) required>
                                        <button type="submit" class="btn btn-sm btn-primary" @disabled($settleMax <= 0)>
                                            <i class="ti ti-check me-1"></i>Settle
                                        </button>
                                    </form>
                                    @if($settleMax <= 0)
                                        <small class="text-muted">No wallet balance</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4">No unpaid labour expenses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Wallet History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive labour-wallet-scroll">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Labour</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>Expense</th>
                            <th>Notes</th>
                            <th>Entry By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $item)
                            <tr>
                                <td>{{ optional($item->current_date)->format('d-m-Y') ?? '-' }}<br><small>{{ $item->current_time }}</small></td>
                                <td>{{ $item->labour?->name ?? '-' }}</td>
                                <td>
                                    @php($typeClass = ['credit' => 'bg-success-transparent text-success', 'withdraw' => 'bg-danger-transparent text-danger', 'settle' => 'bg-info-transparent text-info'][$item->entry_type] ?? 'bg-light text-dark')
                                    <span class="badge {{ $typeClass }}">{{ ucfirst($item->entry_type) }}</span>
                                </td>
                                <td class="text-end fw-semibold">Rs. {{ number_format((float) $item->amount, 2) }}</td>
                                <td>
                                    @if($item->expense)
                                        #{{ $item->expense->id }} {{ $item->expense->project?->name ? '- ' . $item->expense->project->name : '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item->notes ?? '-' }}</td>
                                <td>{{ $item->user?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4">No wallet history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="walletCreditModal" tabindex="-1" aria-labelledby="walletCreditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('labour-expenses.advance-store') }}" class="modal-content border-0 shadow">
                @csrf
                <input type="hidden" name="entry_type" value="credit">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="walletCreditModalLabel">Add Wallet Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Labour</label>
                        <select name="labour_id" class="form-select" required>
                            <option value="">Select labour</option>
                            @foreach($labours as $labour)
                                <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id)>
                                    {{ $labour->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Advance paid to labour">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-plus me-1"></i>Add Amount
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="walletWithdrawModal" tabindex="-1" aria-labelledby="walletWithdrawModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('labour-expenses.advance-store') }}" class="modal-content border-0 shadow">
                @csrf
                <input type="hidden" name="entry_type" value="withdraw">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="walletWithdrawModalLabel">Withdraw Wallet Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Labour</label>
                        <select name="labour_id" class="form-select" required>
                            <option value="">Select labour</option>
                            @foreach($labours as $labour)
                                <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id)>
                                    {{ $labour->name }} - Rs. {{ number_format((float) $labour->advance_amt, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" min="1" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Advance returned or corrected">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" type="submit">
                        <i class="ti ti-arrow-back-up me-1"></i>Withdraw
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
