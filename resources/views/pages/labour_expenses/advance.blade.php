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
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#walletReverseModal">
                    <i class="ti ti-arrow-back-up me-1"></i>Reverse Amount
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
                        <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
                        <small class="text-muted"><i class="ti ti-info-circle"></i> Amount will be debited from your company wallet and added to labour advance balance.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">Select payment method</option>
                            @foreach($paymentMethods as $paymentMethod)
                                <option value="{{ $paymentMethod->id }}" @selected((string) old('payment_method_id') === (string) $paymentMethod->id)>
                                    {{ $paymentMethod->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Advance paid to labour">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-plus me-1"></i>Give Advance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="walletReverseModal" tabindex="-1" aria-labelledby="walletReverseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('labour-expenses.advance-reverse') }}" class="modal-content border-0 shadow" id="reverseWalletForm">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="walletReverseModalLabel">
                        <i class="ti ti-arrow-back-up me-1 text-danger"></i>Reverse Labour Wallet Amount
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Labour <span class="text-danger">*</span></label>
                        <select name="labour_id" id="reverse_labour_id" class="form-select" required>
                            <option value="">Select labour</option>
                            @foreach($labours as $labour)
                                <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id) data-balance="{{ (float) $labour->advance_amt }}">
                                    {{ $labour->name }} (Wallet: Rs. {{ number_format((float) $labour->advance_amt, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Contributor Table Section -->
                    <div class="mb-3 d-none" id="contributorsBreakdownCard">
                        <label class="form-label fw-semibold">Wallet Contributors</label>
                        <div class="table-responsive border rounded mb-2">
                            <table class="table table-sm table-hover align-middle mb-0" id="contributorsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th class="text-end">Original Added</th>
                                        <th class="text-end">Already Reversed</th>
                                        <th class="text-end">Available to Reverse</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="contributorsTableBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contributor Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="reverse_employee_id" class="form-select" required disabled>
                            <option value="">Select Labour First</option>
                        </select>
                        <small class="text-muted" id="employeeHelpText">Showing only employees with eligible remaining contribution.</small>
                    </div>

                    <!-- Available to Reverse Box -->
                    <div class="alert alert-light border py-2 px-3 mb-3 d-none" id="contributorSummaryBox">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted">Selected Contributor:</span> <strong id="summaryEmpName">-</strong><br>
                                <small class="text-muted">Original Added: <span class="fw-semibold text-dark" id="summaryOriginal">Rs. 0.00</span> | Already Reversed: <span class="fw-semibold text-danger" id="summaryReversed">Rs. 0.00</span></small>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block small">Available to Reverse:</span>
                                <h5 class="mb-0 text-success fw-bold" id="summaryAvailable">Rs. 0.00</h5>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Reverse Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="amount" id="reverse_amount" class="form-control" min="0.01" step="0.01" required disabled placeholder="0.00">
                            </div>
                            <small class="text-danger d-none" id="amountValidationMsg"></small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method_id" id="reverse_payment_method_id" class="form-select" required>
                                <option value="">Select payment method</option>
                                @foreach($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->id }}">
                                        {{ $paymentMethod->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes for reversal">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" type="submit" id="reverseSubmitBtn" disabled>
                        <i class="ti ti-arrow-back-up me-1"></i>Reverse Amount
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labourSelect = document.getElementById('reverse_labour_id');
            const employeeSelect = document.getElementById('reverse_employee_id');
            const amountInput = document.getElementById('reverse_amount');
            const submitBtn = document.getElementById('reverseSubmitBtn');
            const breakdownCard = document.getElementById('contributorsBreakdownCard');
            const tableBody = document.getElementById('contributorsTableBody');
            const summaryBox = document.getElementById('contributorSummaryBox');
            const summaryEmpName = document.getElementById('summaryEmpName');
            const summaryOriginal = document.getElementById('summaryOriginal');
            const summaryReversed = document.getElementById('summaryReversed');
            const summaryAvailable = document.getElementById('summaryAvailable');
            const amountError = document.getElementById('amountValidationMsg');

            let contributorsData = [];
            let labourWalletBalance = 0;

            if (!labourSelect) return;

            function resetContributorState() {
                employeeSelect.innerHTML = '<option value="">Select Labour First</option>';
                employeeSelect.disabled = true;
                amountInput.value = '';
                amountInput.disabled = true;
                submitBtn.disabled = true;
                breakdownCard.classList.add('d-none');
                tableBody.innerHTML = '';
                summaryBox.classList.add('d-none');
                amountError.classList.add('d-none');
            }

            labourSelect.addEventListener('change', function () {
                const labourId = this.value;
                if (!labourId) {
                    resetContributorState();
                    return;
                }

                const selectedOption = this.options[this.selectedIndex];
                labourWalletBalance = parseFloat(selectedOption.dataset.balance || '0');

                fetch(`{{ url('labour-expenses/contributors') }}/${labourId}`)
                    .then(res => res.json())
                    .then(data => {
                        contributorsData = data.contributors || [];
                        labourWalletBalance = parseFloat(data.wallet_balance || '0');

                        employeeSelect.innerHTML = '<option value="">Select Contributor Employee</option>';
                        tableBody.innerHTML = '';

                        let eligibleCount = 0;

                        contributorsData.forEach(c => {
                            const tr = document.createElement('tr');
                            const isEligible = c.available_to_reverse > 0 && labourWalletBalance > 0;
                            tr.innerHTML = `
                                <td><strong>${c.employee_name}</strong></td>
                                <td class="text-end">Rs. ${parseFloat(c.original_amount).toFixed(2)}</td>
                                <td class="text-end text-muted">Rs. ${parseFloat(c.already_reversed).toFixed(2)}</td>
                                <td class="text-end ${c.available_to_reverse > 0 ? 'text-success fw-semibold' : 'text-muted'}">Rs. ${parseFloat(c.available_to_reverse).toFixed(2)}</td>
                                <td class="text-center">
                                    ${isEligible ? `<button type="button" class="btn btn-sm btn-outline-primary select-emp-btn py-0 px-2" data-id="${c.employee_id}">Select</button>` : `<span class="badge bg-light text-muted">Exhausted</span>`}
                                </td>
                            `;
                            tableBody.appendChild(tr);

                            if (c.available_to_reverse > 0) {
                                eligibleCount++;
                                const opt = document.createElement('option');
                                opt.value = c.employee_id;
                                opt.textContent = `${c.employee_name} (Avail: Rs. ${parseFloat(c.available_to_reverse).toFixed(2)})`;
                                employeeSelect.appendChild(opt);
                            }
                        });

                        if (contributorsData.length > 0) {
                            breakdownCard.classList.remove('d-none');
                        } else {
                            breakdownCard.classList.remove('d-none');
                            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No recorded wallet contributions for this labour.</td></tr>';
                        }

                        if (eligibleCount > 0 && labourWalletBalance > 0) {
                            employeeSelect.disabled = false;
                        } else {
                            employeeSelect.innerHTML = `<option value="">No eligible contributors (Wallet Balance: Rs. ${labourWalletBalance.toFixed(2)})</option>`;
                            employeeSelect.disabled = true;
                        }

                        document.querySelectorAll('.select-emp-btn').forEach(btn => {
                            btn.addEventListener('click', function () {
                                const empId = this.dataset.id;
                                employeeSelect.value = empId;
                                employeeSelect.dispatchEvent(new Event('change'));
                            });
                        });
                    })
                    .catch(err => {
                        console.error('Error fetching contributors:', err);
                    });
            });

            employeeSelect.addEventListener('change', function () {
                const empId = parseInt(this.value);
                const contributor = contributorsData.find(c => c.employee_id === empId);

                if (!contributor) {
                    summaryBox.classList.add('d-none');
                    amountInput.disabled = true;
                    amountInput.value = '';
                    submitBtn.disabled = true;
                    return;
                }

                const maxReversible = Math.min(contributor.available_to_reverse, labourWalletBalance);

                summaryEmpName.textContent = contributor.employee_name;
                summaryOriginal.textContent = `Rs. ${parseFloat(contributor.original_amount).toFixed(2)}`;
                summaryReversed.textContent = `Rs. ${parseFloat(contributor.already_reversed).toFixed(2)}`;
                summaryAvailable.textContent = `Rs. ${parseFloat(contributor.available_to_reverse).toFixed(2)}`;
                summaryBox.classList.remove('d-none');

                amountInput.disabled = false;
                amountInput.max = maxReversible;
                amountInput.value = maxReversible > 0 ? maxReversible : '';
                validateAmount();
            });

            amountInput.addEventListener('input', validateAmount);

            function validateAmount() {
                const empId = parseInt(employeeSelect.value);
                const contributor = contributorsData.find(c => c.employee_id === empId);
                if (!contributor) return;

                const val = parseFloat(amountInput.value);
                const maxReversible = Math.min(contributor.available_to_reverse, labourWalletBalance);

                if (isNaN(val) || val <= 0) {
                    amountError.textContent = 'Please enter a valid amount greater than 0.';
                    amountError.classList.remove('d-none');
                    submitBtn.disabled = true;
                } else if (val > contributor.available_to_reverse) {
                    amountError.textContent = `Amount cannot exceed employee available contribution of Rs. ${contributor.available_to_reverse.toFixed(2)}.`;
                    amountError.classList.remove('d-none');
                    submitBtn.disabled = true;
                } else if (val > labourWalletBalance) {
                    amountError.textContent = `Amount cannot exceed overall Labour Wallet balance of Rs. ${labourWalletBalance.toFixed(2)}.`;
                    amountError.classList.remove('d-none');
                    submitBtn.disabled = true;
                } else {
                    amountError.classList.add('d-none');
                    submitBtn.disabled = false;
                }
            }

            if (labourSelect.value) {
                labourSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
@endpush
