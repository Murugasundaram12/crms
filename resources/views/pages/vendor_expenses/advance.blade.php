@extends('layouts.app')
@section('title', 'Vendor Advance History')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Vendor Advance History</h4></div>
<div class="card border rounded-0 mb-4"><div class="card-header bg-white border-bottom"><form class="row g-3 align-items-end m-0" method="GET"><div class="col-12 col-md-6 col-lg-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">All Vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor_id')==$vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div><div class="col-12 col-md-6 col-lg-2"><button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button></div></form></div></div>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Vendor</th><th>Advance Amount</th><th>Phone</th></tr></thead><tbody>@forelse($advanceVendors as $vendor)<tr><td>{{ $vendor->name }}</td><td>{{ number_format((float)$vendor->advance_amt,2) }}</td><td>{{ $vendor->phone ?? '-' }}</td></tr>@empty<tr><td colspan="3" class="text-center">No records</td></tr>@endforelse</tbody></table></div>
{{ $advanceVendors->links() }}
@endsection
