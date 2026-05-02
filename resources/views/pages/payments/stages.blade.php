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
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Stage</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <form action="{{ route('payment-stages.index') }}" method="GET"
                class="d-flex align-items-center gap-2 flex-wrap w-100">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                </div>

                <select name="name" class="form-select">
                    <option value="">Stage</option>
                    @foreach ($paymentStages as $stages)
                        <option value="{{ $stages->name }}"
                            @selected(request('name') == $stages->name)>
                            {{ $stages->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive table-nowrap custom-table">
                <table class="table table-nowrap">
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
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                            data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_stage_{{ $stage->id }}"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <button type="button" class="dropdown-item crm-delete-trigger"
                                                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                data-delete-action="{{ route('payment-stages.destroy', $stage) }}"
                                                data-delete-title="Delete Payment Stage"
                                                data-delete-message="Are you sure you want to delete payment stage '{{ $stage->name }}'?"
                                                <i class="ti ti-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No payment stages found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $paymentStages->links() }}</div>
        </div>
    </div>

    <!-- Offcanvas Add -->
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

    <!-- Edit Modals -->
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
@endsection
