@extends('layouts.app')
@section('title', 'Labour Expenses History')
@section('content')
    @include('partials.alerts')

    <div class="card border rounded-0 mb-4">
        <div class="card-header bg-white border-bottom">
        <form method="GET" action="{{ route('labour-expenses.history') }}" class="row g-3 align-items-end m-0">
            <div class="col-md-2">
                <label class="form-label mb-1">Main Category</label>
                <select name="main_category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory->id }}" @selected((string) request('main_category_id') === (string) $mainCategory->id)>{{ $mainCategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Project Name</label>
                <select name="project_id" class="form-select">
                    <option value="">All</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Labour Name</label>
                <select name="labour_id" class="form-select">
                    <option value="">All</option>
                    @foreach($labours as $labour)
                        <option value="{{ $labour->id }}" @selected((string) request('labour_id') === (string) $labour->id)>{{ $labour->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label mb-1">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Text" value="{{ request('q') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                <a href="{{ route('labour-expenses.history') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
            </div>
        </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-tabs-solid nav-justified mb-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('expenses.history') }}"><i class="ti ti-file-invoice me-1"></i>Other Expenses</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('labour-expenses.history') }}"><i class="ti ti-user-cog me-1"></i>Labour</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('vendor-expenses.history') }}"><i class="ti ti-building-warehouse me-1"></i>Vendor</a></li>
            </ul>

            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Paid Date</th>
                                <th>Main Category</th>
                                <th>Category Name</th>
                                <th>Project Name</th>
                                <th>Labour Name</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Unpaid</th>
                                <th>Advanced Amount</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Payment Mode</th>
                                <th>Added By</th>
                                <th>Edited By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td>{{ optional($tx->current_date)->format('d-m-Y') }}<br><small>{{ $tx->current_time ?? '--' }}</small></td>
                                    <td>{{ $tx->mainCategory?->name ?? '-' }}</td>
                                    <td>{{ $tx->category?->name ?? '-' }}</td>
                                    <td>{{ $tx->project?->name ?? '-' }}</td>
                                    <td>{{ $tx->labour?->name ?? '-' }}</td>
                                    <td class="text-warning fw-semibold">{{ number_format((float) $tx->amount, 2) }}</td>
                                    <td class="text-success fw-semibold">{{ number_format((float) $tx->paid_amount, 2) }}</td>
                                    <td class="text-danger fw-semibold">{{ number_format((float) $tx->unpaid_amount, 2) }}</td>
                                    <td class="text-info fw-semibold">{{ number_format((float) $tx->extra_amount, 2) }}</td>
                                    <td>{{ $tx->description ?? '--' }}</td>
                                    <td>@if(!empty($tx->image_path))<img src="{{ asset('storage/' . $tx->image_path) }}" alt="Image" style="max-width:60px;max-height:40px;">@else -- @endif</td>
                                    <td>{{ $tx->payment_mode ?? '--' }}</td>
                                    <td>{{ $tx->user?->name ?? '--' }}</td>
                                    <td>--</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="javascript:void(0);" class="text-success" title="Edit">
                                                <i class="ti ti-edit fs-16"></i>
                                            </a>
                                            <form method="POST" action="{{ route('labour-expenses.delete-record') }}"
                                                onsubmit="return confirm('Delete this labour expense?');">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $tx->id }}">
                                                <input type="hidden" name="delete_reason" value="Deleted from list">
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                    <i class="ti ti-trash fs-16"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="text-center py-4">No records found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <p class="mb-0 text-muted">Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} results</p>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3 flex-wrap gap-4 fw-semibold">
        <div>Total Amount: <span class="text-warning">{{ number_format((float) ($totals->total_amount ?? 0), 2) }}</span></div>
        <div>Total Paid Amount: <span class="text-success">{{ number_format((float) ($totals->total_paid_amount ?? 0), 2) }}</span></div>
        <div>Total Unpaid Amount: <span class="text-danger">{{ number_format((float) ($totals->total_unpaid_amount ?? 0), 2) }}</span></div>
        <div>Total Advanced Amount: <span class="text-info">{{ number_format((float) ($totals->total_advanced_amount ?? 0), 2) }}</span></div>
    </div>
@endsection
