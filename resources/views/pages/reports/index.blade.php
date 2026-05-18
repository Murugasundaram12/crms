@extends('layouts.app')

@section('title', 'Reports')

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

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">{{ $title }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('reports.index', ['type' => $type]) }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body"><p class="mb-1 text-muted">Entries</p><h5 class="mb-0">{{ $summary['count'] ?? 0 }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body"><p class="mb-1 text-muted">Amount</p><h5 class="mb-0">Rs {{ number_format((float) ($summary['total_amount'] ?? 0), 2) }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body"><p class="mb-1 text-muted">Paid</p><h5 class="mb-0">Rs {{ number_format((float) ($summary['paid'] ?? 0), 2) }}</h5></div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body"><p class="mb-1 text-muted">Unpaid</p><h5 class="mb-0">Rs {{ number_format((float) ($summary['unpaid'] ?? 0), 2) }}</h5></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project Name</th>
                            <th>Main Category</th>
                            <th>Sub Category</th>
                            <th>Labour</th>
                            <th>Vendor</th>
                            @if($isSuperAdmin)
                                <th>Income</th>
                            @endif
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Unpaid</th>
                            <th>Description</th>
                            <th>Payment Mode</th>
                            <th>Entry Name</th>
                            <th>Edit Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $row)
                            <tr>
                                <td>{{ $row['date'] ?? '-' }}</td>
                                <td>{{ $row['project_name'] ?? '-' }}</td>
                                <td>{{ $row['main_category'] ?? '-' }}</td>
                                <td>{{ $row['sub_category'] ?? '-' }}</td>
                                <td>{{ $row['labour'] ?? '-' }}</td>
                                <td>{{ $row['vendor'] ?? '-' }}</td>
                                @if($isSuperAdmin)
                                    <td>{{ is_null($row['income']) ? '-' : 'Rs '.number_format((float) $row['income'], 2) }}</td>
                                @endif
                                <td>Rs {{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                                <td>Rs {{ number_format((float) ($row['paid'] ?? 0), 2) }}</td>
                                <td>Rs {{ number_format((float) ($row['unpaid'] ?? 0), 2) }}</td>
                                <td>{{ $row['description'] ?? '-' }}</td>
                                <td>{{ $row['payment_mode'] ?? '-' }}</td>
                                <td>{{ $row['entry_name'] ?? '-' }}</td>
                                <td>{{ $row['edit_name'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">No report data found for the selected filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($records, 'links'))
                <div class="mt-3">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
