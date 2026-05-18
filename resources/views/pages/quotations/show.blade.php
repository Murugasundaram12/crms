@extends('layouts.app')

@section('title', 'Quotation Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1">Quotation #{{ $quotation->id }}</h4>
            <div class="text-muted">{{ $quotation->quotation_number ?? '-' }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('quotations.list') }}" class="btn btn-outline-secondary">Back</a>
            {{-- @if(auth()->user()?->hasPermission('quotations-edit'))
                <a href="{{ route('quotations.edit', $quotation->id) }}" class="btn btn-primary">Edit</a>
            @endif --}}
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted mb-1">Client</div>
                    <div class="fw-semibold">{{ $quotation->client?->name ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted mb-1">Quotation Date</div>
                    <div class="fw-semibold">{{ optional($quotation->quotation_date)->format('d M Y') ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted mb-1">Status</div>
                    <span class="badge {{ $quotation->status === 'approved' ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ ucfirst($quotation->status ?? 'draft') }}
                    </span>
                </div>
                <div class="col-md-8">
                    <div class="text-muted mb-1">Title</div>
                    <div class="fw-semibold">{{ $quotation->quotation_title ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted mb-1">Total Amount</div>
                    <div class="fw-semibold">Rs {{ number_format((float) ($quotation->total_amount ?? $quotation->amount ?? 0), 2) }}</div>
                </div>
                <div class="col-md-12">
                    <div class="text-muted mb-1">Notes</div>
                    <div>{{ $quotation->notes ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Items</h5>
        </div>
        <div class="card-body">
            @php
                $groupedItems = $quotation->items
                    ->sortBy([
                        ['main_title_order', 'asc'],
                        ['item_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->groupBy(fn($item) => $item->main_title ?: 'Untitled Section');
            @endphp

            @forelse($groupedItems as $title => $rows)
                <div class="mb-3">
                    <h6 class="mb-2">{{ $title }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</td>
                                        <td class="text-end">₹ {{ number_format((float) ($item->price ?? $item->rate ?? 0), 2) }}</td>
                                        <td class="text-end">₹ {{ number_format((float) ($item->amount ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No items found.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Terms & Conditions</h5>
        </div>
        <div class="card-body">
            @if($quotation->terms->isNotEmpty())
                <ol class="mb-0">
                    @foreach($quotation->terms as $term)
                        <li>{{ $term->term_text }}</li>
                    @endforeach
                </ol>
            @else
                <p class="text-muted mb-0">No terms available.</p>
            @endif
        </div>
    </div>
</div>
@endsection
