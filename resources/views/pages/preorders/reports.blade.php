@extends('layouts.app')

@section('title', 'Preorder Reports')

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">Preorder ERP Reports & Analytics</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('preorders.index') }}">Preorders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('preorders.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Back to Preorders List
            </a>
        </div>

        <!-- Filter Form -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('preorders.reports') }}" method="GET" class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Material / Tool</label>
                        <select name="tool_material_id" class="form-select">
                            <option value="">All Materials</option>
                            @foreach($toolsMaterials as $material)
                                <option value="{{ $material->id }}" @selected((string) request('tool_material_id') === (string) $material->id)>{{ $material->name }}</option>
                            @endforeach
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
                    <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i> Report</button>
                        <a href="{{ route('preorders.reports') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Total Preorder Value</div>
                        <div class="h4 fw-bold text-dark mb-0">Rs. {{ number_format($metrics['total_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-success border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Total Advance Paid</div>
                        <div class="h4 fw-bold text-success mb-0">Rs. {{ number_format($metrics['total_advance_paid'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-danger border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Total Pending Balance</div>
                        <div class="h4 fw-bold text-danger mb-0">Rs. {{ number_format($metrics['total_pending_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-warning border-3">
                    <div class="card-body">
                        <div class="text-muted fs-12 text-uppercase mb-1">Pending Approval</div>
                        <div class="h4 fw-bold text-warning mb-0">{{ number_format($metrics['pending_approval']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Tabs -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active py-3" data-bs-toggle="tab" data-bs-target="#rpt-vendor"><i class="ti ti-users me-1"></i> Vendor-Wise Summary</button></li>
                    <li class="nav-item"><button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#rpt-material"><i class="ti ti-box me-1"></i> Material-Wise Summary</button></li>
                    <li class="nav-item"><button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#rpt-detail"><i class="ti ti-list me-1"></i> Preorder Detail Report</button></li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">
                    <!-- Tab 1: Vendor-Wise -->
                    <div class="tab-pane fade show active" id="rpt-vendor">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vendor Name</th>
                                        <th>Preorders Count</th>
                                        <th>Total Order Value</th>
                                        <th>Advance Paid</th>
                                        <th>Pending Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vendorSummary as $item)
                                        <tr>
                                            <td class="fw-bold text-dark">{{ $item['vendor_name'] }}</td>
                                            <td>{{ $item['count'] }}</td>
                                            <td class="fw-semibold">Rs. {{ number_format((float) $item['total_amount'], 2) }}</td>
                                            <td class="text-success fw-bold">Rs. {{ number_format((float) $item['advance_paid'], 2) }}</td>
                                            <td class="text-danger fw-bold">Rs. {{ number_format((float) $item['pending_balance'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No vendor preorder records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Material-Wise -->
                    <div class="tab-pane fade" id="rpt-material">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material Name</th>
                                        <th>SKU</th>
                                        <th>Orders Count</th>
                                        <th>Ordered Qty</th>
                                        <th>Delivered Qty</th>
                                        <th>Remaining Qty</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($materialSummary as $item)
                                        <tr>
                                            <td class="fw-bold text-dark">{{ $item['material_name'] }}</td>
                                            <td>{{ $item['sku'] }}</td>
                                            <td>{{ $item['count'] }}</td>
                                            <td>{{ number_format((float) $item['total_qty'], 2) }}</td>
                                            <td class="text-success fw-bold">{{ number_format((float) $item['delivered_qty'], 2) }}</td>
                                            <td class="text-danger fw-bold">{{ number_format((float) $item['remaining_qty'], 2) }}</td>
                                            <td class="fw-semibold">Rs. {{ number_format((float) $item['total_amount'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center py-4 text-muted">No material preorder records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Detailed Preorders -->
                    <div class="tab-pane fade" id="rpt-detail">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ref No</th>
                                        <th>Date</th>
                                        <th>Material</th>
                                        <th>Vendor</th>
                                        <th>Status</th>
                                        <th>Total Value</th>
                                        <th>Advance Paid</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preorders as $p)
                                        <tr>
                                            <td class="fw-bold"><a href="{{ route('preorders.show', $p->id) }}">{{ $p->reference_no }}</a></td>
                                            <td>{{ optional($p->preorder_date)->format('d M Y') }}</td>
                                            <td>{{ $p->toolMaterial?->name }}</td>
                                            <td>{{ $p->vendor?->name ?? 'Unassigned' }}</td>
                                            <td><span class="badge bg-dark">{{ ucfirst($p->status) }}</span></td>
                                            <td>Rs. {{ number_format((float) $p->total_amount, 2) }}</td>
                                            <td class="text-success">Rs. {{ number_format((float) $p->advance_amount, 2) }}</td>
                                            <td class="text-danger">Rs. {{ number_format((float) $p->remaining_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center py-4 text-muted">No records.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
