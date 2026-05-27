@extends('layouts.app')
@section('title', 'Labour Deleted History')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Labour Deleted History</h4>
    </div>
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4"><select name="labour_id" class="form-select">
                <option value="">All Labour</option>@foreach($labours as $labour)<option value="{{ $labour->id }}"
                @selected(request('labour_id') == $labour->id)>{{ $labour->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paid date</th>
                    <th>Main Category</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Project Name</th>
                    <th>Labour Name</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Unpaid</th>
                    <th>Advanced Amount</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Payment Mode</th>
                    <th>Added By</th>
                    <th>Edited By</th>
                    <th>Advance Edited By</th>
                    <th>Deleted Date</th>
                </tr>
            </thead>
            <tbody>@forelse($transactions as $tx)<tr>
                <td>{{ $tx->id }}</td>
                <td>{{ $tx->current_date }}</td>
                <td>{{ $tx->mainCategory?->name ?? '-' }}</td>
                <td>{{ $tx->category?->name ?? '-' }}</td>
                <td>{{ $tx->description ?? '-' }}</td>
                <td>{{ $tx->project?->name ?? '-' }}</td>
                <td>{{ $tx->labour?->name ?? '-' }}</td>
                <td>{{ $tx->delete_reason ?? '-' }}</td>
                <td>{{ number_format((float) $tx->amount, 2) }}</td>
                <td>{{ number_format((float) $tx->paid_amount, 2) }}</td>
                <td>{{ number_format((float) $tx->unpaid_amount, 2) }}</td>
                <td>{{ number_format((float) $tx->extra_amount, 2) }}</td>
                <td>{{ $tx->description ?? '-' }}</td>
                <td>@if(!empty($tx->image_path))<img src="{{ asset('storage/' . $tx->image_path) }}" alt="Image"
                style="max-width:60px;max-height:40px;" />@else - @endif</td>
                <td>{{ $tx->payment_mode ?? '-' }}</td>
                <td>{{ $tx->user?->name ?? '-' }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ $tx->current_date }}</td>
            </tr>@empty<tr>
                    <td colspan="19" class="text-center">No records</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
    {{ $transactions->links() }}
@endsection
