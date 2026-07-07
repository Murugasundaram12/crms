@extends('layouts.app')
@section('title', 'Vendor Deleted History')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Vendor Deleted History</h4></div>
<div class="card border rounded-0 mb-4"><div class="card-header bg-white border-bottom"><form class="row g-3 align-items-end m-0" method="GET"><div class="col-12 col-md-6 col-lg-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">All Vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id')==$vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div><div class="col-12 col-md-6 col-lg-2"><button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button></div></form></div></div>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Vendor</th><th>Paid</th><th>Delete Reason</th><th>Date</th></tr></thead><tbody>@forelse($transactions as $tx)<tr><td>{{ $tx->vendor?->name ?? '-' }}</td><td>{{ number_format((float)$tx->paid_amount,2) }}</td><td>{{ $tx->delete_reason ?? '-' }}</td><td>{{ $tx->current_date }}</td></tr>@empty<tr><td colspan="4" class="text-center">No records</td></tr>@endforelse</tbody></table></div>
{{ $transactions->links() }}
@endsection
