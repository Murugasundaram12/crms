@extends('layouts.app')

@section('title', 'Edit Quotation')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Quotation<span class="badge badge-soft-primary ms-2">{{ $quotation->id }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('quotations.list') }}">Quotations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('quotations.list') }}" class="btn btn-outline-light shadow-sm">Back to Quotations</a>
            @can('quotations-delete')
                <button type="button" class="btn btn-outline-danger crm-delete-trigger" data-bs-toggle="modal"
                    data-bs-target="#crmDeleteModal" data-delete-action="{{ route('quotations.delete', $quotation->id) }}"
                    data-delete-title="Delete Quotation"
                    data-delete-message="Are you sure you want to delete quotation #{{ $quotation->id }}?">
                    <i class="ti ti-trash me-1"></i>Delete
                </button>
            @endcan
        </div>
    </div>

    <form action="{{ route('quotations.update', $quotation->id) }}" method="POST">
        @csrf
        @include('pages.quotations._form')
    </form>
@endsection
