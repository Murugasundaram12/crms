@extends('layouts.app')

@section('title', 'Create Quotation')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Create Quotation<span class="badge bg-soft-primary text-primary ms-2">{{ count($clients) }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quotations.list') }}">Quotations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('quotations.list') }}" class="btn btn-outline-light shadow-sm">Back to Quotations</a>
    </div>

    <form action="{{ route('quotations.store') }}" method="POST">
        @csrf
        @include('pages.quotations._form')
    </form>
@endsection
