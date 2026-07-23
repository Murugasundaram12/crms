@extends('layouts.app')

@section('title', 'Preorder ' . $preorder->reference_no)

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="m-0">Preorder {{ $preorder->reference_no }}</h4>
                    @php
                        $statusBadge = match($preorder->status) {
                            'draft' => 'bg-secondary',
                            'pending_approval' => 'bg-warning text-dark',
                            'approved' => 'bg-success',
                            'ordered' => 'bg-primary',
                            'partially_delivered' => 'bg-info text-dark',
                            'delivered', 'closed' => 'bg-dark',
                            'rejected' => 'bg-danger',
                            'cancelled' => 'bg-danger-transparent text-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $statusBadge }} fs-13">
                        {{ \App\Http\Controllers\PreorderController::STATUSES[$preorder->status] ?? ucfirst($preorder->status) }}
                    </span>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('preorders.index') }}">Preorders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $preorder->reference_no }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('preorders.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to List
                </a>

                <!-- Approval Actions -->
                @if($preorder->status === 'pending_approval' || $preorder->status === 'draft')
                    @can('tools-materials-edit')
                        <form action="{{ route('preorders.approve', $preorder->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i> Approve Preorder</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal_reject">
                            <i class="ti ti-x me-1"></i> Reject
                        </button>
                    @endcan
                @endif

                <!-- Add Advance Action -->
                @can('tools-materials-create')
                    @if($preorder->remaining_amount > 0)
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal_advance">
                            <i class="ti ti-wallet me-1"></i> Add Advance Payment
                        </button>
                    @endif

                    <!-- Record Delivery Action -->
                    @if($preorder->isApproved() && $preorder->remainingQuantity() > 0)
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modal_delivery">
                            <i class="ti ti-truck-delivery me-1"></i> Record Delivery Receipt
                        </button>
                    @endif

                    <!-- Convert to Purchase Action -->
                    @if($preorder->canBeConvertedToPurchase())
                        <a href="{{ route('preorders.convert.form', $preorder->id) }}" class="btn btn-primary">
                            <i class="ti ti-shopping-cart-plus me-1"></i> Convert to Purchase
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        @include('partials.alerts')

        <!-- Stepper Workflow -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-center">
                    @php
                        $steps = ['draft', 'pending_approval', 'approved', 'ordered', 'partially_delivered', 'delivered'];
                        $currentStepIndex = array_search($preorder->status, $steps);
                        if ($currentStepIndex === false) {
                            $currentStepIndex = ($preorder->status === 'closed') ? 5 : -1;
                        }
                    @endphp
                    @foreach($steps as $idx => $step)
                        @php
                            $isDone = $currentStepIndex >= $idx;
                            $isCurrent = $currentStepIndex === $idx;
                        @endphp
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold {{ $isDone ? 'bg-primary text-white' : 'bg-light text-muted' }}" style="width: 32px; height: 32px;">
                                {{ $idx + 1 }}
                            </div>
                            <span class="fs-13 {{ $isCurrent ? 'fw-bold text-primary' : ($isDone ? 'fw-medium text-dark' : 'text-muted') }}">
                                {{ \App\Http\Controllers\PreorderController::STATUSES[$step] }}
                            </span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-grow-1 border-top {{ $isDone ? 'border-primary border-2' : 'border-light' }} d-none d-md-block" style="min-width: 20px;"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Total Estimated Amount</div>
                        <div class="h4 fw-bold text-dark mb-1">Rs. {{ number_format((float) $preorder->total_amount, 2) }}</div>
                        <small class="text-muted">Rate: Rs. {{ number_format((float) $preorder->expected_rate, 2) }} / {{ $preorder->unit }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Total Advance Paid</div>
                        <div class="h4 fw-bold text-success mb-1">Rs. {{ number_format((float) $preorder->advance_amount, 2) }}</div>
                        <small class="text-muted">{{ $preorder->advances->count() }} Advance Payment(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Remaining Balance</div>
                        <div class="h4 fw-bold text-danger mb-1">Rs. {{ number_format((float) $preorder->remaining_amount, 2) }}</div>
                        <small class="text-muted">Payment Status: {{ ucfirst($preorder->payment_status) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Delivery Receipts</div>
                        <div class="h4 fw-bold text-info mb-1">{{ number_format($preorder->totalDeliveredQuantity(), 2) }} / {{ number_format((float) $preorder->quantity, 2) }} {{ $preorder->unit }}</div>
                        <small class="text-muted">Delivery Status: {{ ucfirst($preorder->delivery_status) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Tabbed Interface -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="preorderTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3" id="overview-tab" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button"><i class="ti ti-info-circle me-1"></i> Overview & Workflow</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="advances-tab" data-bs-toggle="tab" data-bs-target="#tab-advances" type="button"><i class="ti ti-wallet me-1"></i> Payment History ({{ $preorder->advances->count() }})</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="deliveries-tab" data-bs-toggle="tab" data-bs-target="#tab-deliveries" type="button"><i class="ti ti-truck-delivery me-1"></i> Deliveries & Stock ({{ $preorder->deliveries->count() }})</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="documents-tab" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button"><i class="ti ti-file-text me-1"></i> Documents ({{ $preorder->documents->count() }})</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" id="audit-tab" data-bs-toggle="tab" data-bs-target="#tab-audit" type="button"><i class="ti ti-history me-1"></i> Audit Trail ({{ $preorder->auditLogs->count() }})</button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="preorderTabsContent">
                    <!-- Tab 1: Overview -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Preorder Information</h6>
                                <table class="table table-borderless table-sm">
                                    <tr><th class="w-40 text-muted">Reference No:</th><td class="fw-bold">{{ $preorder->reference_no }}</td></tr>
                                    <tr><th class="text-muted">Preorder Date:</th><td>{{ optional($preorder->preorder_date)->format('d M Y') }}</td></tr>
                                    <tr><th class="text-muted">Required Date:</th><td>{{ optional($preorder->required_date)->format('d M Y') ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Expected Delivery:</th><td>{{ optional($preorder->expected_delivery_date)->format('d M Y') ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Created By:</th><td>{{ $preorder->creator?->name ?? 'System' }}</td></tr>
                                    <tr><th class="text-muted">Approved By:</th><td>{{ $preorder->approver?->name ?? 'Pending Manager Approval' }}</td></tr>
                                    <tr><th class="text-muted">Approval Date:</th><td>{{ optional($preorder->approval_date)->format('d M Y h:i A') ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-12 col-md-6">
                                <h6 class="fw-bold mb-3 border-bottom pb-2">Material & Vendor Details</h6>
                                <table class="table table-borderless table-sm">
                                    <tr><th class="w-40 text-muted">Material / Tool:</th><td class="fw-bold text-primary">{{ $preorder->toolMaterial?->name }}</td></tr>
                                    <tr><th class="text-muted">SKU:</th><td>{{ $preorder->toolMaterial?->sku ?? '-' }}</td></tr>
                                    <tr><th class="text-muted">Vendor:</th><td class="fw-bold">{{ $preorder->vendor?->name ?? 'Unassigned' }}</td></tr>
                                    <tr><th class="text-muted">Quantity:</th><td>{{ number_format((float) $preorder->quantity, 2) }} {{ $preorder->unit }}</td></tr>
                                    <tr><th class="text-muted">Expected Rate:</th><td>Rs. {{ number_format((float) $preorder->expected_rate, 2) }}</td></tr>
                                    <tr><th class="text-muted">GST ({{ number_format((float) $preorder->gst_percent, 2) }}%):</th><td>Rs. {{ number_format((float) $preorder->gst_amount, 2) }}</td></tr>
                                    <tr><th class="text-muted">Payment Method:</th><td>{{ $preorder->paymentMethod?->name ?? 'Not specified' }}</td></tr>
                                </table>
                            </div>
                            @if($preorder->notes)
                                <div class="col-12">
                                    <div class="bg-light rounded p-3">
                                        <div class="fw-bold mb-1"><i class="ti ti-notes me-1"></i> Notes</div>
                                        <div>{{ $preorder->notes }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Status Transition History -->
                        <h6 class="fw-bold mt-4 mb-3 border-bottom pb-2">Status Workflow History</h6>
                        <div class="timeline">
                            @forelse($preorder->statusHistories as $history)
                                <div class="d-flex gap-3 mb-3">
                                    <div class="rounded-circle bg-primary-transparent text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <i class="ti ti-arrow-right"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            Status changed to <span class="badge bg-primary fs-12">{{ \App\Http\Controllers\PreorderController::STATUSES[$history->to_status] ?? $history->to_status }}</span>
                                        </div>
                                        <small class="text-muted">by {{ $history->changer?->name ?? 'System' }} on {{ $history->created_at->format('d M Y, h:i A') }}</small>
                                        @if($history->notes)
                                            <div class="text-secondary fs-13 mt-1">{{ $history->notes }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No status history recorded yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tab 2: Multiple Advances -->
                    <div class="tab-pane fade" id="tab-advances" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Advance Payment History</h6>
                            @if($preorder->remaining_amount > 0)
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal_advance">
                                    <i class="ti ti-plus me-1"></i> Add Advance Payment
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Ref No</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Paid By</th>
                                        <th>Wallet Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preorder->advances as $adv)
                                        <tr>
                                            <td>{{ optional($adv->payment_date)->format('d M Y') }}</td>
                                            <td class="fw-bold text-dark">{{ $adv->reference_number ?? '-' }}</td>
                                            <td class="text-success fw-bold">Rs. {{ number_format((float) $adv->amount, 2) }}</td>
                                            <td>{{ $adv->paymentMethod?->name ?? '-' }}</td>
                                            <td>{{ $adv->payer?->name ?? '-' }}</td>
                                            <td>
                                                @if($adv->wallet_debited)
                                                    <span class="badge bg-success-transparent text-success"><i class="ti ti-wallet me-1"></i> Debited</span>
                                                @else
                                                    <span class="badge bg-secondary">Manual</span>
                                                @endif
                                            </td>
                                            <td>{{ $adv->notes ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No advance payments recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Deliveries -->
                    <div class="tab-pane fade" id="tab-deliveries" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Delivery Receipts & Stock Receipts</h6>
                            @if($preorder->isApproved() && $preorder->remainingQuantity() > 0)
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modal_delivery">
                                    <i class="ti ti-plus me-1"></i> Record Delivery Receipt
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Delivery No</th>
                                        <th>Delivery Date</th>
                                        <th>Challan No</th>
                                        <th>Quantity Delivered</th>
                                        <th>Received By</th>
                                        <th>Stock Entry</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preorder->deliveries as $del)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $del->delivery_number }}</td>
                                            <td>{{ optional($del->delivery_date)->format('d M Y') }}</td>
                                            <td>{{ $del->challan_no ?? '-' }}</td>
                                            <td class="fw-bold text-dark">{{ number_format((float) $del->quantity, 2) }} {{ $preorder->unit }}</td>
                                            <td>{{ $del->receiver?->name ?? '-' }}</td>
                                            <td>
                                                @if($del->assignment)
                                                    <span class="badge bg-success"><i class="ti ti-check me-1"></i> Stock Increased ({{ $del->assignment->reference_no }})</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $del->notes ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No delivery receipts recorded yet. Stock will increase only when delivery is received.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 4: Documents -->
                    <div class="tab-pane fade" id="tab-documents" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Quotations & Documents</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal_document">
                                <i class="ti ti-upload me-1"></i> Upload Document
                            </button>
                        </div>
                        <div class="row g-3">
                            @forelse($preorder->documents as $doc)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="border rounded p-3 d-flex align-items-center justify-content-between bg-light">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ti ti-file-description text-primary fs-28"></i>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $doc->title }}</div>
                                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }} • {{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB</small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
                                            <form action="{{ route('preorders.documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete document?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-4 text-muted">No documents uploaded yet. You can upload Quotations, Delivery Challans, or Invoices.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tab 5: Audit Log -->
                    <div class="tab-pane fade" id="tab-audit" role="tabpanel">
                        <h6 class="fw-bold mb-3">ERP Action Audit Trail</h6>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Performed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preorder->auditLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('d M Y, h:i:s A') }}</td>
                                            <td><span class="badge bg-dark">{{ strtoupper($log->action) }}</span></td>
                                            <td>{{ $log->description }}</td>
                                            <td>{{ $log->performer?->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No audit logs recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add Advance Payment -->
    <div class="modal fade" id="modal_advance" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('preorders.advances.store', $preorder->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Advance Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label">Payment Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" max="{{ $preorder->remaining_amount }}" min="0.01" required>
                            <small class="text-muted">Remaining Balance: Rs. {{ number_format((float) $preorder->remaining_amount, 2) }}</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method_id" class="form-select" required>
                                <option value="">Select Payment Method</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reference / Transaction Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="TXN/CHEQUE/REF NO">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="deduct_wallet" value="1" id="deduct_wallet" checked>
                                <label class="form-check-label fw-bold" for="deduct_wallet">Deduct from my User Wallet balance</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Advance Payment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Record Delivery Receipt -->
    <div class="modal fade" id="modal_delivery" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('preorders.deliveries.store', $preorder->id) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Delivery Receipt (Increases Stock)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label">Quantity Delivered ({{ $preorder->unit }}) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" class="form-control" max="{{ $preorder->remainingQuantity() }}" min="0.01" required>
                            <small class="text-muted">Remaining Undelivered Qty: {{ number_format($preorder->remainingQuantity(), 2) }} {{ $preorder->unit }}</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Delivery Challan Number</label>
                            <input type="text" name="challan_no" class="form-control" placeholder="Challan / LR No">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info text-white">Record & Add to Stock</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Upload Document -->
    <div class="modal fade" id="modal_document" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('preorders.documents.store', $preorder->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Preorder Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select name="document_type" class="form-select" required>
                                <option value="quotation">Vendor Quotation</option>
                                <option value="purchase_order">Purchase Order (PO)</option>
                                <option value="delivery_challan">Delivery Challan</option>
                                <option value="invoice">Vendor Invoice</option>
                                <option value="vendor_pdf">Vendor Specification PDF</option>
                                <option value="other">Other Attachment</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Document Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Quotation V1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Select File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload File</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reject Preorder -->
    <div class="modal fade" id="modal_reject" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('preorders.reject', $preorder->id) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Preorder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
