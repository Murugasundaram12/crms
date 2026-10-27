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

        @media print {
            .sidebar, .header, .report-toolbar, .report-tabs, .pagination, .btn, #reportTableSearchWrapper, .report-chip, .no-print {
                display: none !important;
            }
            .main-wrapper, .page-wrapper, .content, .report-shell, .report-card {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .report-table {
                font-size: 10px !important;
            }
            .report-table th, .report-table td {
                padding: 4px 6px !important;
            }
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    @php
        $user = auth()->user();
        $isSuperAdmin = $user && (method_exists($user, 'isSuperAdmin')
            ? $user->isSuperAdmin()
            : (method_exists($user, 'assignedRoles')
                ? $user->assignedRoles()->contains('name', 'Super Admin')
                : (($user->role ?? '') === 'Super Admin')));

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

                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        @foreach($projects ?? [] as $proj)
                            <option value="{{ $proj->id }}" @selected(($filters['project_id'] ?? '') == $proj->id)>{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-xl-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('reports.index', ['type' => $type]) }}" class="btn btn-outline-secondary w-100 shadow-sm">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        @php
            $hasIncome = $isSuperAdmin && isset($summary['income']);
            $colClass = $hasIncome ? 'col-6 col-md-4 col-xl-2' : 'col-md-6 col-xl-3';
            $netBalance = $hasIncome ? ((float) ($summary['income'] ?? 0) - (float) ($summary['paid'] ?? 0)) : 0;
        @endphp

        <div class="row g-3 mb-4">
            <div class="{{ $colClass }}">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <p class="mb-1 text-muted fs-12">Entries</p>
                            <h5 class="mb-0 fs-16">{{ number_format((int) ($summary['count'] ?? 0)) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-primary-transparent text-primary"><i class="ti ti-list-details"></i></span>
                    </div>
                </div>
            </div>
            <div class="{{ $colClass }}">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <p class="mb-1 text-muted fs-12">Total Expenses</p>
                            <h5 class="mb-0 fs-16 text-warning">Rs {{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-warning-transparent text-warning"><i class="ti ti-receipt-rupee"></i></span>
                    </div>
                </div>
            </div>
            <div class="{{ $colClass }}">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <p class="mb-1 text-muted fs-12">Expenses Paid</p>
                            <h5 class="mb-0 fs-16 text-success">Rs {{ number_format((float) ($summary['paid'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-success-transparent text-success"><i class="ti ti-circle-check"></i></span>
                    </div>
                </div>
            </div>
            <div class="{{ $colClass }}">
                <div class="report-stat h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <p class="mb-1 text-muted fs-12">Expenses Unpaid</p>
                            <h5 class="mb-0 fs-16 text-danger">Rs {{ number_format((float) ($summary['unpaid'] ?? 0), 2) }}</h5>
                        </div>
                        <span class="report-stat-icon bg-danger-transparent text-danger"><i class="ti ti-alert-circle"></i></span>
                    </div>
                </div>
            </div>
            @if($hasIncome)
                <div class="{{ $colClass }}">
                    <div class="report-stat h-100">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <p class="mb-1 text-muted fs-12">Income Received</p>
                                <h5 class="mb-0 fs-16 text-info">Rs {{ number_format((float) ($summary['income'] ?? 0), 2) }}</h5>
                            </div>
                            <span class="report-stat-icon bg-info-transparent text-info"><i class="ti ti-wallet"></i></span>
                        </div>
                    </div>
                </div>
                <div class="{{ $colClass }}">
                    <div class="report-stat h-100">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <p class="mb-1 text-muted fs-12">Net Balance</p>
                                <h5 class="mb-0 fs-16 {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($netBalance, 2) }}</h5>
                            </div>
                            <span class="report-stat-icon {{ $netBalance >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}"><i class="ti {{ $netBalance >= 0 ? 'ti-trending-up' : 'ti-trending-down' }}"></i></span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="report-card">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap p-3 border-bottom">
                <div>
                    <h5 class="mb-1">{{ $title }} List</h5>
                    <p class="text-muted mb-0 fs-13">Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ number_format($records->total()) }} entries</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap" id="reportTableSearchWrapper">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-white border-end-0"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" id="reportTableSearchInput" class="form-control border-start-0" placeholder="Filter rows...">
                    </div>
                    <span class="report-chip no-print">
                        <i class="ti ti-calendar-stats me-1"></i>
                        {{ filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) || filled($filters['project_id'] ?? null) ? 'Filtered' : 'All records' }}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()" title="Print Report">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success no-print" id="exportReportCsvBtn" title="Export CSV">
                        <i class="ti ti-file-spreadsheet me-1"></i>Export CSV
                    </button>
                </div>
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
                                <th>Paid Payment Income</th>
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
                                <td colspan="{{ $isSuperAdmin ? 14 : 13 }}" class="text-center py-5">
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Live table row filter
            const searchInput = document.getElementById('reportTableSearchInput');
            const table = document.querySelector('.report-table');
            if (searchInput && table) {
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');

                searchInput.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();
                    rows.forEach(function (row) {
                        if (row.cells.length <= 1) return;
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }

            // CSV Export
            const exportBtn = document.getElementById('exportReportCsvBtn');
            if (exportBtn && table) {
                exportBtn.addEventListener('click', function () {
                    let csv = [];
                    const rows = table.querySelectorAll('tr');

                    rows.forEach(function (row) {
                        if (row.style.display === 'none' || row.cells.length <= 1) return;
                        let rowData = [];
                        const cols = row.querySelectorAll('th, td');
                        cols.forEach(function (col) {
                            let text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                            text = text.replace(/"/g, '""');
                            rowData.push('"' + text + '"');
                        });
                        csv.push(rowData.join(','));
                    });

                    if (csv.length <= 1) {
                        alert('No data available to export.');
                        return;
                    }

                    const csvContent = '\uFEFF' + csv.join('\r\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.setAttribute('href', url);
                    link.setAttribute('download', 'Report_{{ $type }}_' + new Date().toISOString().slice(0, 10) + '.csv');
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        });
    </script>
@endpush
