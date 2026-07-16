@extends('layouts.app')

@section('title', 'Tools & Materials')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tools & Materials<span class="badge badge-soft-primary ms-2">{{ $toolsMaterials->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tools & Materials</li>
                </ol>
            </nav>
        </div>
        @can('tools-materials-create')
            <a href="{{ route('tools-materials.create') }}" class="btn btn-primary shadow-sm">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Tool / Material
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('tools-materials.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search name" value="{{ request('q') }}">
                    </div>
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
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('tools-materials.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($toolsMaterials as $item)
                            <tr>
                                <td>
                                    @if($item->image_path)
                                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}" class="rounded border" style="width: 52px; height: 52px; object-fit: cover;">
                                    @else
                                        <span class="avatar bg-light text-muted"><i class="ti ti-photo"></i></span>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td>{{ $item->date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $item->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('tools-materials.edit', $item)"
                                        editPermission="tools-materials-edit"
                                        :deleteRoute="route('tools-materials.destroy', $item)"
                                        deleteTitle="Delete Tool / Material"
                                        :deleteMessage="'Are you sure you want to delete \'' . $item->name . '\'?'"
                                        deletePermission="tools-materials-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No tools or materials found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($toolsMaterials->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $toolsMaterials->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
