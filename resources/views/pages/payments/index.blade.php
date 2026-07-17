@extends('layouts.app')

@section('title', 'Payments')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Payments<span class="badge badge-soft-primary ms-2">{{ $payments->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payments</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            @can('payments-create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Payment
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('payments.index') }}" method="GET" class="row g-3 align-items-end m-0">
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
                        @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'overdue' => 'Overdue', 'partial' => 'Partial'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Quotation</label>
                    <select name="quotation_number" class="form-select">
                        <option value="">Quotation Number</option>
                        @foreach ($quotations as $quotation)
                            <option value="{{ $quotation->quotation_number }}" @selected(request('quotation_number') == $quotation->quotation_number)>
                                {{ $quotation->quotation_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>


    <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive table-nowrap custom-table">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Quotation Number</th>
                        <th>Client</th>
                        <th>Project</th>
                        <th>Stage</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Payment Method</th>
                        {{-- <th>Transaction ID</th> --}}
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->quotation?->quotation_number ?? '-' }}</td>
                            <td>{{ $payment->client?->name ?? '-' }}</td>
                            <td>{{ $payment->project?->name ?? '-' }}</td>
                            <td>{{ $payment->stage->name ?? '-' }}</td>
                            <td class="fw-semibold">Rs. {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ optional($payment->due_date)->format('d M Y') ?? '-' }}</td>
                            <td>{{ $payment->method ? ucfirst(str_replace('_', ' ', $payment->method)) : '-' }}</td>
                            {{-- <td>{{ $payment->transaction_id ?: '-' }}</td> --}}
                            <td>
                                <span
                                    class="badge {{ $payment->status === 'paid' ? 'bg-success-transparent text-success' : ($payment->status === 'overdue' ? 'bg-danger-transparent text-danger' : 'bg-warning-transparent text-warning') }}">
                                    {{ $payment->status ? ucfirst($payment->status) : '-' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <x-action-dropdown :editRoute="in_array($payment->status, ['pending', 'partial', 'overdue']) ? '#' : null" :editAttributes="in_array($payment->status, ['pending', 'partial', 'overdue']) ? ['data-bs-toggle' => 'modal', 'data-bs-target' => '#edit_payment_' . $payment->id] : []"
                                    :deleteRoute="route('payments.destroy', $payment)" deleteTitle="Delete Payment"
                                    editPermission="payments-edit" deletePermission="payments-delete"
                                    :deleteMessage="'Are you sure you want to delete payment for quotation \'' . ($payment->quotation?->quotation_number ?? $payment->id) . '\'?'">
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('payments.show', $payment) }}" title="Show">
                                        <i class="ti ti-eye me-1"></i>Show
                                    </a>
                                </x-action-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $payments->withQueryString()->links() }}
    </div>
    </div>

    {{-- <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 rounded-0">
                <div class="card-header">
                    <h5 class="mb-0">Recent Expenses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive custom-table">
                        <table class="table table-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Project</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenses as $expense)
                                <tr>
                                    <td>{{ $expense->title }}</td>
                                    <td>{{ $expense->project?->name ?? '-' }}</td>
                                    <td>{{ $expense->employee?->name ?? '-' }}</td>
                                    <td>{{ optional($expense->expense_date)->format('d M Y') ?? '-' }}</td>
                                    <td>${{ number_format($expense->amount, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No expenses recorded yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Add Payment Offcanvas -->
    @can('payments-create')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('payments.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Client <span class="text-danger">*</span></label>
                    <select id="client_id" name="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Project <span class="text-danger">*</span></label>
                    <select id="project_id" name="project_id" class="form-select" required>
                        <option value="">Select Project</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Quotation <span class="text-danger">*</span></label>
                    <select id="quotation_id" name="quotation_id" class="form-select" required>
                        <option value="">Select Quotation</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total Amount</label>
                    <input type="number" step="0.01" id="total_amount" class="form-control" readonly>
                </div>
                <input type="hidden" id="remaining_amount" class="payment-remaining-amount">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Stage <span class="text-danger">*</span></label>
                    <select name="stage_id" class="form-select" required>
                        <option value="">Select Stage</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->id }}">
                                {{ $stage->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="amount" name="amount" class="form-control" min="0.01" required readonly>
                    <div class="invalid-feedback payment-amount-error">Amount cannot exceed remaining quotation amount.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="method" class="form-select payment-method-select" required>
                        <option value="">Select</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Paid At</label>
                    <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="col-md-6 transaction-id-field d-none">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    @can('payments-edit')
    @foreach ($payments as $payment)
        <div class="modal fade" id="edit_payment_{{ $payment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('payments.update', $payment) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label">Client <span class="text-danger">*</span></label>
                                <select id="edit_client_id_{{ $payment->id }}" name="client_id" class="form-select" required>
                                    <option value="">Select Client</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected($client->id === $payment->client_id)>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select id="edit_project_id_{{ $payment->id }}" name="project_id" class="form-select"
                                    data-selected="{{ $payment->project_id }}" required>
                                    <option value="{{ $payment->project_id }}" selected>
                                        {{ $payment->project?->name ?? 'Selected Project' }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Quotation <span class="text-danger">*</span></label>
                                <select id="edit_quotation_id_{{ $payment->id }}" name="quotation_id" class="form-select"
                                    data-selected="{{ $payment->quotation_id }}" required>
                                    <option value="{{ $payment->quotation_id }}"
                                        data-total="{{ (float) ($payment->quotation?->total_amount ?? 0) > 0 ? $payment->quotation?->total_amount : ($payment->quotation?->amount ?? 0) }}"
                                        data-remaining="{{ $payment->remaining_amount_for_edit ?? ($payment->quotation?->total_amount ?? $payment->quotation?->amount ?? 0) }}"
                                        selected>
                                        {{ $payment->quotation?->quotation_number ?? 'Selected Quotation' }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Amount</label>
                                <input type="number" step="0.01" id="edit_total_amount_{{ $payment->id }}"
                                    class="form-control"
                                    value="{{ (float) ($payment->quotation?->total_amount ?? 0) > 0 ? $payment->quotation?->total_amount : ($payment->quotation?->amount ?? 0) }}"
                                    readonly>
                            </div>
                            <input type="hidden" id="edit_remaining_amount_{{ $payment->id }}" class="payment-remaining-amount">
                            <div class="col-12">
                                <label class="form-label">Stage <span class="text-danger">*</span></label>
                                <select name="stage_id" class="form-select" required>
                                    <option value="">Select Stage</option>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage->id }}" @selected($stage->id == $payment->stage_id)>
                                            {{ $stage->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" min="0.01"
                                    value="{{ $payment->amount }}" required>
                                <div class="invalid-feedback payment-amount-error">Amount cannot exceed remaining quotation amount.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <select name="method" class="form-select payment-method-select" required>
                                    <option value="">Select</option>
                                    <option value="cash" {{ $payment->method == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ $payment->method == 'bank_transfer' ? 'selected' : '' }}>Bank
                                        Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Paid At</label>
                                <input type="datetime-local" name="paid_at" class="form-control"
                                    value="{{ optional($payment->payment_date)->format('Y-m-d\TH:i') }}">
                            </div>
                            <div class="col-md-6 transaction-id-field {{ $payment->method === 'bank_transfer' ? '' : 'd-none' }}">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control"
                                    value="{{ $payment->transaction_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach (['pending', 'paid', 'overdue', 'partial'] as $status)
                                        <option value="{{ $status }}" @selected($status === $payment->status)>{{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="4">{{ $payment->notes }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    @endcan
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            function validatePaymentAmount($amount) {
                var max = parseFloat($amount.attr('max') || 0);
                var value = parseFloat($amount.val() || 0);
                var isInvalid = max > 0 && value > max;
                var $form = $amount.closest('form');

                $amount.toggleClass('is-invalid', isInvalid);
                $form.find('.payment-amount-error').toggle(isInvalid);
                $form.find('button[type="submit"]').prop('disabled', isInvalid);

                return !isInvalid;
            }

            function syncPaymentMethodFields($form) {
                var method = $form.find('.payment-method-select').val();
                var $field = $form.find('.transaction-id-field');
                var $input = $field.find('input[name="transaction_id"]');

                if (method === 'bank_transfer') {
                    $field.removeClass('d-none');
                    $input.prop('required', true);
                    return;
                }

                $field.addClass('d-none');
                $input.prop('required', false).val('');
            }

            function applyAmountRules(statusSelector, amountSelector, remainingSelector) {
                var status = $(statusSelector).val();
                var remaining = parseFloat($(remainingSelector).val() || 0);
                var $amount = $(amountSelector);
                if (remaining > 0) {
                    $amount.attr('max', remaining);
                } else {
                    $amount.removeAttr('max');
                }

                if (status) {
                    $amount.prop('readonly', false);
                    validatePaymentAmount($amount);
                    return;
                }

                $amount.val('').prop('readonly', true);
                validatePaymentAmount($amount);
            }

            function loadProjects(clientId, projectSelector, selectedProjectId) {
                var $project = $(projectSelector);
                $project.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (!clientId) {
                    $project.empty().append('<option value="">Select Project</option>').prop('disabled', false);
                    return;
                }

                var pUrl = "{{ route('payments.projects-by-client', ':id') }}".replace(':id', clientId);
                $.get(pUrl, function (data) {
                    $project.empty().append('<option value="">Select Project</option>');
                    data.forEach(function (project) {
                        var selected = selectedProjectId && String(selectedProjectId) === String(project.id) ? 'selected' : '';
                        $project.append(`<option value="${project.id}" ${selected}>${project.name}</option>`);
                    });
                    $project.prop('disabled', false).trigger('change');
                }).fail(function () {
                    $project.empty().append('<option value="">Error loading projects</option>').prop('disabled', false);
                });
            }

            function loadQuotations(projectId, quotationSelector, selectedQuotationId) {
                var $quotation = $(quotationSelector);
                $quotation.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (!projectId) {
                    $quotation.empty().append('<option value="">Select Quotation</option>').prop('disabled', false);
                    return;
                }

                var qUrl = "{{ route('payments.quotations-by-project', ':id') }}".replace(':id', projectId);
                $.get(qUrl, function (data) {
                    $quotation.empty().append('<option value="">Select Quotation</option>');

                    data.forEach(function (q) {
                        var selected = selectedQuotationId && String(selectedQuotationId) === String(q.id) ? 'selected' : '';
                        var fullyPaidText = q.is_fully_paid ? ' (Fully Paid)' : '';
                        $quotation.append(
                            `<option value="${q.id}" data-total="${q.total_amount}" data-remaining="${q.remaining_amount}" ${selected}>${q.number}${fullyPaidText}</option>`
                        );
                    });

                    $quotation.prop('disabled', false).trigger('change');
                }).fail(function () {
                    $quotation.empty().append('<option value="">Error loading quotations</option>').prop('disabled', false);
                });
            }

            $('#client_id').on('change', function () {
                var clientId = $(this).val();
                $('#total_amount').val('');
                $('#remaining_amount').val('');
                $('#status').val('');
                $('#amount').val('').prop('readonly', true);
                $('#quotation_id').empty().append('<option value="">Select Quotation</option>');
                loadProjects(clientId, '#project_id', null);
            });

            $('#project_id').on('change', function () {
                var projectId = $(this).val();
                $('#total_amount').val('');
                $('#remaining_amount').val('');
                $('#amount').val('').prop('readonly', true);
                loadQuotations(projectId, '#quotation_id', null);
            });

            @foreach ($payments as $payment)
                $('#edit_payment_{{ $payment->id }}').on('shown.bs.modal', function () {
                    var clientId = $('#edit_client_id_{{ $payment->id }}').val();
                    var selectedProjectId = $('#edit_project_id_{{ $payment->id }}').data('selected');
                    var selectedQuotationId = $('#edit_quotation_id_{{ $payment->id }}').data('selected');
                    loadProjects(clientId, '#edit_project_id_{{ $payment->id }}', selectedProjectId);
                    if (selectedProjectId) {
                        loadQuotations(selectedProjectId, '#edit_quotation_id_{{ $payment->id }}', selectedQuotationId);
                    }
                });

                $('#edit_client_id_{{ $payment->id }}').on('change', function () {
                    var clientId = $(this).val();
                    $('#edit_total_amount_{{ $payment->id }}').val('');
                    $('#edit_remaining_amount_{{ $payment->id }}').val('');
                    loadProjects(clientId, '#edit_project_id_{{ $payment->id }}', null);
                    $('#edit_quotation_id_{{ $payment->id }}').empty().append('<option value="">Select Quotation</option>');
                });

                $('#edit_project_id_{{ $payment->id }}').on('change', function () {
                    var projectId = $(this).val();
                    $('#edit_total_amount_{{ $payment->id }}').val('');
                    $('#edit_remaining_amount_{{ $payment->id }}').val('');
                    loadQuotations(projectId, '#edit_quotation_id_{{ $payment->id }}', null);
                });
            @endforeach

            $(document).on('change', '#quotation_id, [id^=edit_quotation_id_]', function () {
                var remaining = $(this).find(':selected').data('remaining') || 0;
                var total = $(this).find(':selected').data('total') || '';
                var $form = $(this).closest('form');
                var $formAmount = $form.find('input[name="amount"]');
                $form.find('input[id="total_amount"], input[id^="edit_total_amount_"]').val(total);
                $form.find('.payment-remaining-amount').val(remaining);
                if (remaining > 0) {
                    $formAmount.attr('max', remaining);
                } else {
                    $formAmount.removeAttr('max');
                }
                validatePaymentAmount($formAmount);
                if ($(this).attr('id') === 'quotation_id') {
                    applyAmountRules('#status', '#amount', '#remaining_amount');
                }
            });

            $('#status').on('change', function () {
                applyAmountRules('#status', '#amount', '#remaining_amount');
            });

            $(document).on('input', '#amount, input[name="amount"]', function () {
                validatePaymentAmount($(this));
            });

            $(document).on('change', '.payment-method-select', function () {
                syncPaymentMethodFields($(this).closest('form'));
            });

            $('form').each(function () {
                syncPaymentMethodFields($(this));
                $(this).find('.payment-amount-error').hide();
            });
        });
    </script>
@endpush
