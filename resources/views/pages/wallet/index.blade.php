@extends('layouts.app')

@section('title', 'Wallet History')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Wallet History</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Wallet History</li>
                </ol>
            </nav>
        </div>
        @can('transfers-create')
            <a href="{{ route('wallet.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Add Wallet
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Filtered Total</p>
                        <h5 class="mb-0 text-primary">Rs. {{ number_format((float) $totalAmount, 2) }}</h5>
                    </div>
                    <span class="avatar avatar-md rounded bg-primary-transparent text-primary d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-wallet fs-22"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
    <form method="GET" action="{{ route('wallet.index') }}" class="row g-3 align-items-end m-0">
        <div class="col-md-1">
            <label class="form-label mb-1">Entries</label>
            <select name="paginate" class="form-select">
                @foreach([10, 25, 50, 100] as $count)
                    <option value="{{ $count }}" @selected((int) request('paginate', 10) === $count)>{{ $count }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">From</label>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">To</label>
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">Member</label>
            <select name="user_id" class="form-select">
                <option value="">All</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">Client</label>
            <select name="client_id" class="form-select">
                <option value="">All</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">Project</label>
            <select name="project_id" class="form-select">
                <option value="">All</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-1">Search</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="credited, debited, text">
        </div>
        <div class="col-md-1 d-flex gap-2">
            <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
            <a class="btn btn-outline-secondary w-100 shadow-sm" href="{{ route('wallet.index') }}">Reset</a>
        </div>
    </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Received Date</th>
                            <th>Member Name</th>
                            <th>Client Name</th>
                            <th>Project Name</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Payment Mode</th>
                            <th>Description</th>
                            <th>Stage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                            <tr>
                                <td>{{ $wallet->id }}</td>
                                <td>
                                    {{ optional($wallet->current_date)->format('d-m-Y') }}
                                    <br>
                                    <small>{{ optional($wallet->current_date)->format('h:i A') }}</small>
                                </td>
                                <td>{{ $wallet->user?->name ?? '-' }}</td>
                                <td>{{ $wallet->client?->name ?? '-' }}</td>
                                <td>{{ $wallet->project?->name ?? '-' }}</td>
                                <td class="fw-semibold">{{ number_format((float) $wallet->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ (int) $wallet->transfer_type === 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">
                                        {{ (int) $wallet->transfer_type === 0 ? 'Credited' : 'Debited' }}
                                    </span>
                                </td>
                                <td>{{ $paymentModes[(int) $wallet->payment_mode] ?? '-' }}</td>
                                <td>{{ $wallet->description ?? '-' }}</td>
                                <td>{{ $wallet->stage?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">No wallet records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <p class="mb-0 text-muted">
            Showing {{ $wallets->firstItem() ?? 0 }} to {{ $wallets->lastItem() ?? 0 }} of {{ $wallets->total() }} results
        </p>
        {{ $wallets->links() }}
    </div>

@endsection
