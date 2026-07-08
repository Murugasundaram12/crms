@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
    <style>
        .report-shell {
            max-width: 100%;
        }

        .report-toolbar,
        .report-card {
            border: 1px solid #e9edf3;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .report-tabs {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            border: 1px solid #e8edf5;
            border-radius: 8px;
            background: #f8fafc;
        }

        .report-tab {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            border-radius: 6px;
            padding: 0 12px;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
        }

        .report-tab:hover {
            color: #0d6efd;
        }

        .report-tab.active {
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .report-stat {
            border: 1px solid #edf1f6;
            border-radius: 8px;
            background: #fff;
            padding: 16px;
            min-height: 104px;
        }

        .report-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .report-table th {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-table td {
            vertical-align: middle;
            color: #1f2937;
        }

        .report-table .description-cell {
            max-width: 260px;
            white-space: normal;
        }

        .report-money {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
            font-weight: 700;
        }

        .report-chip {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border-radius: 6px;
            padding: 0 8px;
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    @php
        $user = auth()->user();
        $isSuperAdmin = $user && (method_exists($user, 'assignedRoles')
            ? $user->assignedRoles()->contains('name', 'Super Admin')
            : (($user->role ?? '') === 'Super Admin'));

        $title = match ($type) {
            'office' => 'Office Report',
            'total' => 'Total Report',
            default => 'Site Report',
        };
    @endphp

    <div class="report-shell">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">{{ $title }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </nav>
            </div>
            <div class="report-tabs">
                <a class="report-tab @class(['active' => $type === 'site'])" href="{{ route('reports.index', ['type' => 'site']) }}">
                    <i class="ti ti-building-skyscraper me-1"></i>Site
                </a>
                <a class="report-tab @class(['active' => $type === 'office'])" href="{{ route('reports.index', ['type' => 'office']) }}">
                    <i class="ti ti-briefcase me-1"></i>Office
                </a>
                <a class="report-tab @class(['active' => $type === 'total'])" href="{{ route('reports.index', ['type' => 'total']) }}">
                    <i class="ti ti-chart-bar me-1"></i>Total
                </a>
            </div>
        </div>

        <div class="report-toolbar p-3 mb-4">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-12 col-md-6 col-xl-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('reports.index', ['type' => $type]) }}" class="btn btn-outline-secondary w-100 shadow-sm">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <p class="mb-1 text-muted">Entries</p>
                            <h5 class="mb-0">{{ number_format((int) ($summary['count'] ?? 0)) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-primary-transparent text-primary"><i class="ti ti-list-details"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <p class="mb-1 text-muted">Amount</p>
                            <h5 class="mb-0">Rs {{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-warning-transparent text-warning"><i class="ti ti-receipt-rupee"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <p class="mb-1 text-muted">Paid</p>
                            <h5 class="mb-0 text-success">Rs {{ number_format((float) ($summary['paid'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-success-transparent text-success"><i class="ti ti-circle-check"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <p class="mb-1 text-muted">Unpaid</p>
                            <h5 class="mb-0 text-danger">Rs {{ number_format((float) ($summary['unpaid'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-danger-transparent text-danger"><i class="ti ti-alert-circle"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-card">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 border-bottom">
                <div>
                    <h5 class="mb-1">{{ $title }} List</h5>
                    <p class="text-muted mb-0 fs-13">Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ number_format($records->total()) }} entries</p>
                </div>
                <span class="report-chip">
                    <i class="ti ti-calendar-stats me-1"></i>
                    {{ filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) ? 'Filtered' : 'All dates' }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Main Category</th>
                            <th>Sub Category</th>
                            <th>Labour</th>
                            <th>Vendor</th>
                            @if($isSuperAdmin)
                                <th>Income</th>
                            @endif
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Unpaid</th>
                            <th>Description</th>
                            <th>Mode</th>
                            <th>Entry</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $row)
                            <tr>
                                <td>{{ $row['date'] ?? '-' }}</td>
                                <td class="fw-semibold">{{ $row['project_name'] ?? '-' }}</td>
                                <td><span class="report-chip">{{ $row['main_category'] ?? '-' }}</span></td>
                                <td>{{ $row['sub_category'] ?? '-' }}</td>
                                <td>{{ $row['labour'] ?? '-' }}</td>
                                <td>{{ $row['vendor'] ?? '-' }}</td>
                                @if($isSuperAdmin)
                                    <td class="report-money text-success">{{ is_null($row['income']) ? '-' : 'Rs '.number_format((float) $row['income'], 2) }}</td>
                                @endif
                                <td class="text-end report-money">Rs {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                                <td class="text-end report-money text-success">Rs {{ number_format((float) ($row['paid'] ?? 0), 2) }}</td>
                                <td class="text-end report-money text-danger">Rs {{ number_format((float) ($row['unpaid'] ?? 0), 2) }}</td>
                                <td class="description-cell">{{ $row['description'] ?? '-' }}</td>
                                <td>{{ $row['payment_mode'] ?? '-' }}</td>
                                <td>{{ $row['entry_name'] ?? '-' }}</td>
                                <td>{{ $row['edit_name'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-5">
                                    <span class="avatar avatar-lg bg-light text-muted mb-2">
                                        <i class="ti ti-file-search fs-24"></i>
                                    </span>
                                    <h6 class="mb-1">No report data found</h6>
                                    <p class="text-muted mb-0">Try changing the date range or report type.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 border-top">
                <p class="text-muted mb-0 fs-13">
                    Page {{ $records->currentPage() }} of {{ $records->lastPage() }}
                </p>
                @if(method_exists($records, 'links'))
                    <div>
                        {{ $records->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
