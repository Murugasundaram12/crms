@extends('layouts.app')

@section('title', 'Purchase List')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Purchase List</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Purchase List</li>
                </ol>
            </nav>
        </div>
        @can('tools-materials-create')
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('preorders.create') }}" class="btn btn-outline-primary shadow-sm">
                    <i class="ti ti-clock-pause me-1"></i>New Preorder
                </a>
                <a href="{{ route('tools-material-assignments.create', ['transaction_type' => 'purchase', 'destination_type' => 'office', 'lock_transaction' => 1]) }}" class="btn btn-success shadow-sm">
                    <i class="ti ti-shopping-cart-plus me-1"></i>Purchase Stock
                </a>
                <a href="{{ route('tools-material-assignments.create', ['transaction_type' => 'issue_to_site', 'status' => 'draft', 'lock_transaction' => 1]) }}" class="btn btn-warning shadow-sm">
                    <i class="ti ti-truck-delivery me-1"></i>Issue to Site
                </a>
                <a href="{{ route('tools-materials.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Tool / Material
                </a>
            </div>
        @endcan
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Total Stock Items</span><h4 class="mb-0">{{ $summary['items'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Tools / Materials</span><h4 class="mb-0">{{ $summary['tools'] }} / {{ $summary['materials'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Total Stock Value</span><h4 class="mb-0">Rs {{ number_format($summary['stock_value'], 2) }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Low Stock Alert</span><h4 class="mb-0 text-danger">{{ $summary['low_stock'] }}</h4></div></div>
        </div>
    </div>

    <!-- 3 Tabs Navigation -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-tabs-bottom border-0 px-3 pt-2" id="purchaseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'preorder' ? 'active fw-bold' : '' }}" href="{{ route('tools-materials.index', array_merge(request()->query(), ['tab' => 'preorder'])) }}">
                        <i class="ti ti-clock me-1"></i>Preorder List
                        <span class="badge bg-primary-soft text-primary ms-1">{{ $preorders->total() }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'purchase' ? 'active fw-bold' : '' }}" href="{{ route('tools-materials.index', array_merge(request()->query(), ['tab' => 'purchase'])) }}">
                        <i class="ti ti-box me-1"></i>Purchase List (Stock)
                        <span class="badge bg-success-soft text-success ms-1">{{ $toolsMaterials->total() }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'issue_to_site' ? 'active fw-bold' : '' }}" href="{{ route('tools-materials.index', array_merge(request()->query(), ['tab' => 'issue_to_site'])) }}">
                        <i class="ti ti-truck-delivery me-1"></i>Issue to Site List
                        <span class="badge bg-warning-soft text-warning ms-1">{{ $issueAssignments->total() }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body bg-light-subtle border-bottom">
            <form action="{{ route('tools-materials.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search reference, item name, vendor..." value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">Status Filter</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @if($activeTab === 'preorder')
                            @foreach(\App\Http\Controllers\PreorderController::STATUSES as $val => $lbl)
                                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                            @endforeach
                        @elseif($activeTab === 'purchase')
                            <option value="active" @selected(request('status') === 'active')>Active Stock</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        @elseif($activeTab === 'issue_to_site')
                            <option value="draft" @selected(request('status') === 'draft')>Draft (Not Deducted)</option>
                            <option value="transferred" @selected(request('status') === 'transferred')>Confirmed / Transferred</option>
                            <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        @endif
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="ti ti-filter me-1"></i>Filter</button>
                    <a href="{{ route('tools-materials.index', ['tab' => $activeTab]) }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab Contents -->
    @if($activeTab === 'preorder')
        <!-- TAB 1: PREORDER LIST -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive custom-table">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref No</th>
                                <th>Preorder Date</th>
                                <th>Item Name</th>
                                <th>Vendor</th>
                                <th>Qty / Rate</th>
                                <th>Total Amount</th>
                                <th>Advance Paid</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preorders as $po)
                                <tr>
                                    <td><a href="{{ route('preorders.show', $po->id) }}" class="fw-semibold text-primary">{{ $po->reference_no }}</a></td>
                                    <td>{{ $po->preorder_date?->format('d M Y') }}</td>
                                    <td class="fw-semibold text-dark">{{ $po->toolMaterial->name }}</td>
                                    <td>{{ $po->vendor?->name ?: 'N/A' }}</td>
                                    <td>{{ number_format($po->quantity, 2) }} {{ $po->unit }} @ Rs {{ number_format($po->rate, 2) }}</td>
                                    <td class="fw-semibold">Rs {{ number_format($po->total_amount, 2) }}</td>
                                    <td><span class="badge badge-soft-success">Rs {{ number_format($po->advance_amount, 2) }}</span></td>
                                    <td class="fw-bold text-danger">Rs {{ number_format($po->remaining_amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ match($po->status) {
                                             'delivered', 'closed' => 'badge-soft-dark',
                                             'approved' => 'badge-soft-success',
                                             'ordered' => 'badge-soft-primary',
                                             'partially_delivered' => 'badge-soft-info',
                                             'rejected', 'cancelled' => 'badge-soft-danger',
                                             'hold' => 'badge-soft-warning',
                                             default => 'badge-soft-secondary'
                                         } }}">
                                             {{ \App\Http\Controllers\PreorderController::STATUSES[$po->status] ?? ucfirst($po->status) }}
                                         </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @if($po->status !== 'delivered')
                                                <a href="{{ route('preorders.convert.form', $po) }}" class="btn btn-sm btn-success" title="Convert to Purchase">
                                                    <i class="ti ti-shopping-cart-check me-1"></i>Convert
                                                </a>
                                                <a href="{{ route('preorders.edit', $po) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="ti ti-edit"></i>
                                                </a>
                                            @endif
                                            <form action="{{ route('preorders.destroy', $po) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this preorder?');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <i class="ti ti-clock fs-1 d-block text-muted mb-2"></i>
                                        No preorders found for the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($preorders->hasPages())
                <div class="card-footer bg-white d-flex justify-content-end">
                    {{ $preorders->withQueryString()->links() }}
                </div>
            @endif
        </div>

    @elseif($activeTab === 'purchase')
        <!-- TAB 2: PURCHASE LIST / STOCK BALANCE -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive custom-table">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Type</th>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Unit</th>
                                <th>Opening Stock</th>
                                <th>Office Stock</th>
                                <th>Site Stock</th>
                                <th>Stock (Current Balance)</th>
                                <th>Stock Value</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($toolsMaterials as $item)
                                <tr>
                                    <td>
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                        @else
                                            <span class="avatar bg-light text-muted"><i class="ti ti-photo"></i></span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ ucfirst($item->item_type) }}</span></td>
                                    <td>{{ $item->sku ?: '-' }}</td>
                                    <td class="fw-semibold text-dark">{{ $item->name }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>{{ number_format((float) $item->opening_quantity, 2) }} {{ $item->unit }}</td>
                                    <td>{{ number_format($item->office_stock_quantity, 2) }} {{ $item->unit }}</td>
                                    <td>{{ number_format($item->site_stock_quantity, 2) }} {{ $item->unit }}</td>
                                    <td class="fw-bold {{ $item->is_low_stock ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($item->stock_quantity, 2) }} {{ $item->unit }}
                                        @if($item->is_low_stock)
                                            <span class="badge badge-soft-danger ms-1">Low</span>
                                        @endif
                                    </td>
                                    <td>Rs {{ number_format($item->stock_amount, 2) }}</td>
                                    <td>{{ $item->date?->format('d M Y') ?: '-' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @can('tools-materials-create')
                                                <a class="btn btn-sm btn-success" title="Purchase Stock" href="{{ route('tools-material-assignments.create', [
                                                    'tool_material_id' => $item->id,
                                                    'transaction_type' => 'purchase',
                                                    'destination_type' => 'office',
                                                    'lock_transaction' => 1,
                                                ]) }}">
                                                    <i class="ti ti-shopping-cart-plus me-1"></i>Buy
                                                </a>
                                                <a class="btn btn-sm btn-outline-warning" title="Issue to Site" href="{{ route('tools-material-assignments.create', [
                                                    'tool_material_id' => $item->id,
                                                    'transaction_type' => 'issue_to_site',
                                                    'status' => 'draft',
                                                    'quantity' => $item->office_stock_quantity > 0 ? min((float) $item->office_stock_quantity, 1) : 1,
                                                    'rate' => $item->opening_rate,
                                                    'lock_transaction' => 1,
                                                ]) }}">
                                                    <i class="ti ti-truck-delivery me-1"></i>Issue
                                                </a>
                                            @endcan
                                            <a href="{{ route('tools-materials.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-5">
                                        <i class="ti ti-box fs-1 d-block text-muted mb-2"></i>
                                        No purchase stock items found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($toolsMaterials->hasPages())
                <div class="card-footer bg-white d-flex justify-content-end">
                    {{ $toolsMaterials->withQueryString()->links() }}
                </div>
            @endif
        </div>

    @elseif($activeTab === 'issue_to_site')
        <!-- TAB 3: ISSUE TO SITE LIST -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive custom-table">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref No</th>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Destination Site</th>
                                <th>Issued Qty</th>
                                <th>Rate</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Receiver</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issueAssignments as $assignment)
                                <tr>
                                    <td><span class="fw-semibold text-warning">{{ $assignment->reference_no }}</span></td>
                                    <td>{{ $assignment->transferred_at?->format('d M Y H:i') }}</td>
                                    <td class="fw-semibold text-dark">{{ $assignment->toolMaterial?->name }}</td>
                                    <td><span class="badge badge-soft-info">{{ $assignment->toProject?->name ?: 'Site' }}</span></td>
                                    <td>{{ number_format($assignment->quantity, 2) }} {{ $assignment->unit }}</td>
                                    <td>Rs {{ number_format($assignment->rate, 2) }}</td>
                                    <td class="fw-semibold">Rs {{ number_format($assignment->amount, 2) }}</td>
                                    <td>
                                        @if($assignment->status === 'draft')
                                            <span class="badge badge-soft-secondary"><i class="ti ti-file-text me-1"></i>Draft (Stock Intact)</span>
                                        @else
                                            <span class="badge badge-soft-success"><i class="ti ti-check me-1"></i>Confirmed (Stock Deducted)</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment->receiver_name ?: '-' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            @if(auth()->user()->hasPermission('tools-materials-edit'))
                                                <a href="{{ route('tools-material-assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary" title="Edit / Confirm">
                                                    <i class="ti ti-edit me-1"></i>{{ $assignment->status === 'draft' ? 'Edit / Confirm' : 'Edit' }}
                                                </a>
                                            @endif
                                            @if(auth()->user()->hasPermission('tools-materials-delete'))
                                                <form action="{{ route('tools-material-assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this issue transaction?');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <i class="ti ti-truck-delivery fs-1 d-block text-muted mb-2"></i>
                                        No issue to site records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($issueAssignments->hasPages())
                <div class="card-footer bg-white d-flex justify-content-end">
                    {{ $issueAssignments->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
