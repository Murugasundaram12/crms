@extends('layouts.app')

@section('title', 'Payment Stages')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Payment Stages<span class="badge badge-soft-primary ms-2">{{ $paymentStages->total() }}</span>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment Stages</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            @can('payment-stages-create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Stage</a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('payment-stages.index') }}" method="GET"
                class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Stage</label>
                    <select name="name" class="form-select">
                        <option value="">Stage</option>
                        @foreach ($paymentStages as $stages)
                            <option value="{{ $stages->name }}" @selected(request('name') == $stages->name)>
                                {{ $stages->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Filter</button>
                    <a href="{{ route('payment-stages.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive table-nowrap custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Action</th>
                        </tr>

                    </thead>
                    <tbody>
                        @forelse ($paymentStages as $stage)
                            <tr>
                                <td>{{ $stage->name }}</td>
                                <td class="text-end">
                                    <div class="table-action d-inline-flex align-items-center justify-content-end gap-1 flex-wrap">
                                        @can('payment-stages-edit')
                                            <a class="btn btn-sm btn-outline-primary" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_stage_{{ $stage->id }}"><i
                                                    class="ti ti-edit me-1"></i>Edit</a>
                                        @endcan
                                        @can('payment-stages-delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger"
                                                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                data-delete-action="{{ route('payment-stages.destroy', $stage) }}"
                                                data-delete-title="Delete Payment Stage"
                                                data-delete-message="Are you sure you want to delete payment stage '{{ $stage->name }}'?">
                                                <i class="ti ti-trash me-1"></i>Delete
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">No payment stages found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $paymentStages->withQueryString()->links() }}</div>
        </div>
    </div>

    <!-- Offcanvas Add -->
    @can('payment-stages-create')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Payment Stage</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('payment-stages.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">

                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>

                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Stage</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <!-- Edit Modals -->
    @can('payment-stages-edit')
    @foreach ($paymentStages as $stage)
        <div class="modal fade" id="edit_stage_{{ $stage->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Stage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('payment-stages.update', $stage) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $stage->name }}">
                            </div>

                            <!-- Similar fields -->
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    @endcan
@endsection
