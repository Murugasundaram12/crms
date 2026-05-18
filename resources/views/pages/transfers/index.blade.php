@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h4 class="m-0">Transfers</h4>
            <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">Add Transfer</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('transfers.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            value="{{ request('search') }}" placeholder="Type to search" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date (dd/mm/yyyy)</label>
                        <input type="text" name="from_date" class="form-control form-control-sm"
                            value="{{ request('from_date') }}" placeholder="dd/mm/yyyy" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date (dd/mm/yyyy)</label>
                        <input type="text" name="to_date" class="form-control form-control-sm"
                            value="{{ request('to_date') }}" placeholder="dd/mm/yyyy" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="transfer_type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="employee" {{ request('transfer_type') === 'employee' ? 'selected' : '' }}>Employee
                            </option>
                            <option value="vendor" {{ request('transfer_type') === 'vendor' ? 'selected' : '' }}>Vendor
                            </option>
                        </select>
                    </div>
                    <div class="col-md-12 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Employee/Vendor</th>
                        <th>Payment Mode</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Description</th>
                        <th style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $i => $transfer)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ ucfirst($transfer->transfer_type) }}</td>
                            <td>
                                @if($transfer->transfer_type === 'employee')
                                    {{ $transfer->employee_id ?? '-' }}
                                @else
                                    {{ $transfer->vendor_id ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $transfer->payment_mode }}</td>
                            <td>{{ number_format((float) $transfer->amount, 2) }}</td>
                            <td>{{ $transfer->current_date ? \Carbon\Carbon::parse($transfer->current_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $transfer->current_time }}</td>
                            <td>{{ $transfer->description ?? '-' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('transfers.edit', $transfer->id) }}"
                                        class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" action="{{ route('transfers.destroy', $transfer->id) }}"
                                        onsubmit="return confirm('Delete this transfer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $transfers->links() }}
        </div>
    </div>
@endsection