@extends('layouts.app')
@section('title', $title)
@section('content')
    @include('partials.alerts')
    <div class="card border-0 shadow-sm"><div class="card-body"><h4>{{ $title }}</h4><form action="{{ $action }}" method="POST" enctype="multipart/form-data">@csrf<input class="form-control mb-3" type="file" name="file" accept=".xlsx,.xls,.csv" required><button class="btn btn-primary">Import</button></form><p class="text-muted mt-3 mb-0">Required columns: name, phone, status. Existing phone numbers are updated.</p></div></div>
@endsection
