@extends('layouts.app')
@section('title', 'Labour Weekly History')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Labour Weekly History</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('labour-expenses.history') }}">Labour Expenses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Weekly History</li>
                </ol>
            </nav>
        </div>
        <span class="badge bg-light text-dark border">{{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</span>
    </div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white border-bottom">
    <form class="row g-3 align-items-end m-0" method="GET">
        <div class="col-12 col-md-6 col-lg-4"><label class="form-label">Labour</label><select name="labour_id" class="form-select">
                <option value="">All Labour</option>@foreach($labours as $labour)<option value="{{ $labour->id }}"
                @selected(request('labour_id') == $labour->id)>{{ $labour->name }}</option>@endforeach
            </select></div>
        <div class="col-12 col-md-6 col-lg-3 d-flex gap-2">
            <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
            <a href="{{ route('labour-expenses.weekly') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
        </div>
    </form>
    </div></div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover table-nowrap align-middle mb-0">
            <thead class="table-light">
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
                    <td>Rs {{ number_format((float) $weekUnpaid, 2) }}</td>
                    <td>Rs {{ number_format((float) $weekAdvance, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
        </div>
    </div>
@endsection
