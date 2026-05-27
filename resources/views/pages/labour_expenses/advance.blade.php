@extends('layouts.app')
@section('title', 'Labour Advance History')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Labour Advance History</h4></div>
<form class="row g-2 mb-3" method="GET"><div class="col-md-4"><select name="labour_id" class="form-select"><option value="">All Labour</option>@foreach($labours as $labour)<option value="{{ $labour->id }}" @selected(request('labour_id')==$labour->id)>{{ $labour->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-outline-primary" type="submit">Filter</button></div></form>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Labour</th><th>Type</th><th>Amount</th><th>Notes</th><th>Date</th></tr></thead><tbody>@forelse($history as $item)<tr><td>{{ $item->labour?->name ?? '-' }}</td><td>{{ $item->entry_type }}</td><td>{{ number_format((float)$item->amount,2) }}</td><td>{{ $item->notes ?? '-' }}</td><td>{{ $item->current_date }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records</td></tr>@endforelse</tbody></table></div>
{{ $history->links() }}
@endsection
