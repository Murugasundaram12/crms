@extends('layouts.app')

@section('title', 'Preorders')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Preorders & ERP Purchase Workflow</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Preorders</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('preorders.reports') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-chart-bar me-1"></i> Preorder Reports
                </a>
                @can('tools-materials-create')
                    <a href="{{ route('preorders.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Create Preorder
                    </a>
                @endcan
            </div>
        </div>

        @include('partials.alerts')

        <!-- KPI Dashboard Widgets -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Total Preorders</div>
                        <div class="h4 mb-0 fw-bold text-dark">{{ number_format($metrics['total']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-3">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Pending Approval</div>
                        <div class="h4 mb-0 fw-bold text-warning">{{ number_format($metrics['pending_approval']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Approved</div>
                        <div class="h4 mb-0 fw-bold text-success">{{ number_format($metrics['approved']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-3">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Ordered / In-Transit</div>
                        <div class="h4 mb-0 fw-bold text-primary">{{ number_format($metrics['ordered']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-3">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Partially Delivered</div>
                        <div class="h4 mb-0 fw-bold text-info">{{ number_format($metrics['partially_delivered']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-xl-2">
                <div class="card border-0 shadow-sm h-100 border-start border-secondary border-3">
                    <div class="card-body p-3">
                        <div class="text-muted fs-12 fw-medium text-uppercase mb-1">Delivered / Closed</div>
                        <div class="h4 mb-0 fw-bold text-secondary">{{ number_format($metrics['delivered']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="card-title mb-0"><i class="ti ti-filter me-1"></i> Advanced Search & Filters</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('preorders.index') }}" method="GET" class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" placeholder="Ref No, Notes, Material, Vendor" value="{{ request('q') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Material / Tool</label>
                        <select name="tool_material_id" class="form-select">
                            <option value="">All Materials</option>
                            @foreach($toolsMaterials as $item)
                                <option value="{{ $item->id }}" @selected((string) request('tool_material_id') === (string) $item->id)>
                                    {{ $item->name }} (SKU: {{ $item->sku ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Workflow Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All Payment</option>
                            <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option>
                            <option value="partially_paid" @selected(request('payment_status') === 'partially_paid')>Partially Paid</option>
                            <option value="paid" @selected(request('payment_status') === 'paid')>Fully Paid</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Delivery Status</label>
                        <select name="delivery_status" class="form-select">
                            <option value="">All Delivery</option>
                            <option value="pending" @selected(request('delivery_status') === 'pending')>Pending</option>
                            <option value="partially_delivered" @selected(request('delivery_status') === 'partially_delivered')>Partially Delivered</option>
                            <option value="delivered" @selected(request('delivery_status') === 'delivered')>Delivered</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search me-1"></i> Filter</button>
                        <a href="{{ route('preorders.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preorder Master Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref No</th>
                                <th>Preorder Date</th>
                                <th>Material / Tool</th>
                                <th>Vendor</th>
                                <th>Qty (Delivered / Total)</th>
                                <th>Total Amount</th>
                                <th>Advance Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($preorders as $preorder)
                                @php
                                    $deliveredQty = $preorder->totalDeliveredQuantity();
                                    $totalQty = (float) $preorder->quantity;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('preorders.show', $preorder->id) }}" class="fw-bold text-primary">
                                            {{ $preorder->reference_no }}
                                        </a>
                                    </td>
                                    <td>{{ optional($preorder->preorder_date)->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ $preorder->toolMaterial?->name ?? '-' }}</div>
                                        <small class="text-muted">SKU: {{ $preorder->toolMaterial?->sku ?? '-' }}</small>
                                    </td>
                                    <td>{{ $preorder->vendor?->name ?? 'Unassigned' }}</td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ number_format($deliveredQty, 2) }}</span> / {{ number_format($totalQty, 2) }} {{ $preorder->unit }}
                                        @if($totalQty > 0)
                                            <div class="progress mt-1" style="height: 4px; width: 100px;">
                                                <div class="progress-bar bg-success" style="width: {{ min(100, ($deliveredQty / $totalQty) * 100) }}%"></div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">Rs. {{ number_format((float) $preorder->total_amount, 2) }}</td>
                                    <td class="text-success fw-semibold">Rs. {{ number_format((float) $preorder->advance_amount, 2) }}</td>
                                    <td class="text-danger fw-semibold">Rs. {{ number_format((float) $preorder->remaining_amount, 2) }}</td>
                                    <td>
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
                                        <span class="badge {{ $statusBadge }}">
                                            {{ \App\Http\Controllers\PreorderController::STATUSES[$preorder->status] ?? ucfirst($preorder->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item" href="{{ route('preorders.show', $preorder->id) }}"><i class="ti ti-eye me-1 text-primary"></i> View Details</a></li>
                                                @can('tools-materials-edit')
                                                    <li><a class="dropdown-item" href="{{ route('preorders.edit', $preorder->id) }}"><i class="ti ti-edit me-1 text-info"></i> Edit Preorder</a></li>
                                                @endcan
                                                @if($preorder->canBeConvertedToPurchase())
                                                    @can('tools-materials-create')
                                                        <li><a class="dropdown-item" href="{{ route('preorders.convert.form', $preorder->id) }}"><i class="ti ti-shopping-cart-plus me-1 text-success"></i> Convert / Receive Purchase</a></li>
                                                    @endcan
                                                @endif
                                                @can('tools-materials-delete')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('preorders.destroy', $preorder->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this preorder?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"><i class="ti ti-trash me-1"></i> Delete</button>
                                                        </form>
                                                    </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">No preorders found matching your filter criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($preorders->hasPages())
                <div class="card-footer bg-white border-top py-3">
                    {{ $preorders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
