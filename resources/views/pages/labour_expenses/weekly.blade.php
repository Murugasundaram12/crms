@extends('layouts.app')
@section('title', 'Labour Weekly History')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Labour Weekly History</h4><span>{{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</span>
    </div>
    <div class="card border rounded-0 mb-4"><div class="card-header bg-white border-bottom">
    <form class="row g-3 align-items-end m-0" method="GET">
        <div class="col-12 col-md-6 col-lg-4"><label class="form-label">Labour</label><select name="labour_id" class="form-select">
                <option value="">All Labour</option>@foreach($labours as $labour)<option value="{{ $labour->id }}"
                @selected(request('labour_id') == $labour->id)>{{ $labour->name }}</option>@endforeach
            </select></div>
        <div class="col-12 col-md-6 col-lg-2"><button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button></div>
    </form>
    </div></div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Unpaid Amount</th>
                    <th>Advance Amount</th>
                </tr>
            </thead>
            <tbody>@php
                // $transactions is an array of records for the week; compute totals per week.
                $grouped = $transactions instanceof \Illuminate\Support\Collection ? $transactions : collect($transactions);
                $weekUnpaid = $grouped->sum(fn($t) => (float) ($t->unpaid_amount ?? 0));
                $weekAdvance = 0;
            @endphp
                <tr>
                    <td>{{ $start->format('Y-[W]W') }}</td>
                    <td>{{ number_format((float) $weekUnpaid, 2) }}</td>
                    <td>{{ number_format((float) $weekAdvance, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
