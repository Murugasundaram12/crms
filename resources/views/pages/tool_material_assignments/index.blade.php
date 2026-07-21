@extends('layouts.app')

@section('title', 'Tool / Material Transfers')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tool / Material Transfers<span class="badge badge-soft-primary ms-2">{{ $assignments->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tool / Material Transfers</li>
                </ol>
            </nav>
        </div>
        @can('tools-materials-create')
            <a href="{{ route('tools-material-assignments.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Assign / Transfer
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Transactions</span><h4 class="mb-0">{{ $summary['transactions'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Completed</span><h4 class="mb-0">{{ $summary['completed'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Material Value</span><h4 class="mb-0">Rs {{ number_format($summary['amount'], 2) }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Vendor Returns</span><h4 class="mb-0">Rs {{ number_format($summary['vendor_returns'], 2) }}</h4></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('tools-material-assignments.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Ref / receiver / vehicle" value="{{ request('q') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Tool Name</label>
                    <select name="tool_material_id" class="form-select">
                        <option value="">All Tools</option>
                        @foreach($toolsMaterials as $tool)
                            <option value="{{ $tool->id }}" @selected((string) request('tool_material_id') === (string) $tool->id)>{{ $tool->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Site Name</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Sites</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Transaction</label>
                    <select name="transaction_type" class="form-select">
                        <option value="">All</option>
                        @foreach($transactionTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('transaction_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('tools-material-assignments.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ref No</th>
                            <th>Tool Name</th>
                            <th>Status</th>
                            <th>Transaction</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Receiver</th>
                            <th>Vehicle</th>
                            <th>Handled By</th>
                            <th>Date & Time</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            @php
                                $currentSiteId = null;
                                $currentSiteName = null;
                                $siteBalance = 0.0;
                                $canShowMoveActions = false;
                                $hasMovableStock = false;

                                if ($assignment->destination_type === 'site' && $assignment->to_project_id) {
                                    $currentSiteId = $assignment->to_project_id;
                                    $currentSiteName = $assignment->toProject?->name;
                                } elseif ($assignment->source_type === 'site' && $assignment->from_project_id) {
                                    $currentSiteId = $assignment->from_project_id;
                                    $currentSiteName = $assignment->fromProject?->name;
                                } else {
                                    if ($assignment->to_project_id) {
                                        $currentSiteId = $assignment->to_project_id;
                                        $currentSiteName = $assignment->toProject?->name;
                                    } elseif ($assignment->from_project_id) {
                                        $currentSiteId = $assignment->from_project_id;
                                        $currentSiteName = $assignment->fromProject?->name;
                                    }
                                }

                                if (
                                    \App\Models\ToolMaterialAssignment::isStockEffectiveStatus($assignment->status)
                                    && $assignment->destination_type === 'site'
                                    && $currentSiteId
                                    && $assignment->toolMaterial
                                ) {
                                    $siteBalance = (float) ($assignment->toolMaterial->stockBalances()['site:' . $currentSiteId]['quantity'] ?? 0);
                                    $canShowMoveActions = true;
                                    $hasMovableStock = $siteBalance > 0;
                                }

                                $moveQuantity = $siteBalance > 0 ? min((float) $assignment->quantity, $siteBalance) : max((float) $assignment->quantity, 1);
                                $moveAmount = round($moveQuantity * (float) $assignment->rate, 2);
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $assignment->reference_no }}</td>
                                <td class="fw-semibold">{{ $assignment->toolMaterial?->name ?? '-' }}</td>
                                <td>
                                    @php($statusClass = match ($assignment->status) {
                                        'transferred' => 'bg-primary',
                                        'returned', 'completed' => 'bg-success',
                                        default => 'bg-secondary',
                                    })
                                    <span class="badge {{ $statusClass }}">
                                        {{ $assignment->statusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-soft-info text-info">
                                        {{ $assignment->transactionLabel() }}
                                    </span>
                                </td>
                                <td>{{ $assignment->source_type === 'site' ? ($assignment->fromProject?->name ?? '-') : ucfirst((string) $assignment->source_type) }}</td>
                                <td>
                                    @if($assignment->destination_type === 'site')
                                        {{ $assignment->toProject?->name ?? '-' }}
                                    @elseif($assignment->destination_type === 'vendor')
                                        {{ $assignment->vendor?->name ?? 'Vendor' }}
                                    @else
                                        {{ ucfirst((string) $assignment->destination_type) }}
                                    @endif
                                </td>
                                <td>{{ number_format((float) $assignment->quantity, 2) }} {{ $assignment->unit }}</td>
                                <td>Rs {{ number_format((float) $assignment->amount, 2) }}</td>
                                <td>{{ $assignment->receiver_name ?: '-' }}</td>
                                <td>{{ $assignment->vehicle_no ?: '-' }}</td>
                                <td>{{ $assignment->handler?->name ?? '-' }}</td>
                                <td>{{ $assignment->transferred_at?->format('d M Y h:i A') ?: '-' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap tm-action-group">
                                        @if(auth()->user()?->hasPermission('tools-materials-create') && $canShowMoveActions)
                                            @if($hasMovableStock)
                                                <a class="btn btn-sm btn-light-success tm-action-btn" title="Return to Office" href="{{ route('tools-material-assignments.create', [
                                                    'tool_material_id' => $assignment->tool_material_id,
                                                    'transaction_type' => 'return_to_office',
                                                    'status' => 'returned',
                                                    'source_type' => 'site',
                                                    'destination_type' => 'office',
                                                    'from_project_id' => $currentSiteId,
                                                    'quantity' => $moveQuantity,
                                                    'rate' => $assignment->rate,
                                                    'amount' => $moveAmount,
                                                    'purpose' => 'Return from ' . ($currentSiteName ?? 'site'),
                                                    'lock_transaction' => 1,
                                                ]) }}">
                                                    <i class="ti ti-arrow-back-up"></i>
                                                    <span>Return</span>
                                                </a>
                                                <a class="btn btn-sm btn-light-primary tm-action-btn" title="Transfer to Site" href="{{ route('tools-material-assignments.create', [
                                                    'tool_material_id' => $assignment->tool_material_id,
                                                    'transaction_type' => 'site_to_site',
                                                    'status' => 'transferred',
                                                    'source_type' => 'site',
                                                    'destination_type' => 'site',
                                                    'from_project_id' => $currentSiteId,
                                                    'quantity' => $moveQuantity,
                                                    'rate' => $assignment->rate,
                                                    'amount' => $moveAmount,
                                                    'purpose' => 'Transfer from ' . ($currentSiteName ?? 'site'),
                                                    'lock_transaction' => 1,
                                                ]) }}">
                                                    <i class="ti ti-arrows-transfer-up-down"></i>
                                                    <span>Transfer</span>
                                                </a>
                                            @else
                                                <span class="badge bg-light text-muted border tm-stock-empty">No site stock</span>
                                            @endif
                                        @endif
                                        <x-action-dropdown
                                            :editRoute="route('tools-material-assignments.edit', $assignment)"
                                            editPermission="tools-materials-edit"
                                            :deleteRoute="route('tools-material-assignments.destroy', $assignment)"
                                            deleteTitle="Delete Transfer"
                                            :deleteMessage="'Are you sure you want to delete this transfer?'"
                                            deletePermission="tools-materials-delete"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">No transfer records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($assignments->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $assignments->withQueryString()->links() }}
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .tm-action-group {
                min-width: max-content;
                vertical-align: middle;
            }

            .tm-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                border: 0;
                border-radius: 6px;
                font-weight: 600;
                line-height: 1;
                padding: 7px 9px;
                white-space: nowrap;
            }

            .tm-action-btn i {
                font-size: 15px;
                line-height: 1;
            }

            .btn-light-success.tm-action-btn {
                background: #eaf8ef;
                color: #1e7e43;
            }

            .btn-light-success.tm-action-btn:hover {
                background: #d8f1e2;
                color: #176b37;
            }

            .btn-light-primary.tm-action-btn {
                background: #edf4ff;
                color: #1f5fbf;
            }

            .btn-light-primary.tm-action-btn:hover {
                background: #dbeaff;
                color: #184f9f;
            }

            .tm-action-group .table-action {
                display: inline-flex;
            }

            .tm-action-group .action-icon {
                width: 32px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .tm-stock-empty {
                border-radius: 6px;
                font-weight: 600;
                padding: 8px 10px;
                white-space: nowrap;
            }
        </style>
    @endpush
@endsection
