@extends('layouts.app')

@section('title', 'Main Categories')

@section('content')
    @include('partials.alerts')

    <div id="statusAlertContainer" class="mb-3"></div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Main Categories<span
                    class="badge badge-soft-primary ms-2">{{ $mainCategories->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Main Categories</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('main-categories-create')
                <a href="{{ route('main_categories.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Main Category
                </a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('main_categories.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search main categories" value="{{ request('q') }}">
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
                    <a href="{{ route('main_categories.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mainCategories as $mainCategory)
                            <tr>
                                <td>{{ $mainCategory->name }}</td>
                                <td>
                                    <div class="form-check form-switch form-switch-sm">
                                        <input class="form-check-input toggle-status" type="checkbox" role="switch"
                                            id="status{{ $mainCategory->id }}" data-id="{{ $mainCategory->id }}" {{ $mainCategory->status === 'active' ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>{{ $mainCategory->created_at?->format('d M Y') ?: '-' }}</td>
                                <td class="text-end">
                                    <x-action-dropdown
                                        :editRoute="route('main_categories.edit', $mainCategory->id)"
                                        editPermission="main-categories-edit"
                                        :deleteRoute="route('main_categories.destroy', $mainCategory->id)"
                                        deleteTitle="Delete Main Category"
                                        :deleteMessage="'Are you sure you want to delete main category \'' . $mainCategory->name . '\'?'"
                                        deletePermission="main-categories-delete"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No main categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($mainCategories->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $mainCategories->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertContainer = document.getElementById('statusAlertContainer');

            function showStatusAlert(type, message) {
                if (!alertContainer) {
                    return;
                }

                alertContainer.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }

            document.querySelectorAll('.toggle-status').forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const statusToggle = this;
                    const id = statusToggle.dataset.id || statusToggle.getAttribute('id').replace('status', '');
                    const newStatus = statusToggle.checked ? 'active' : 'inactive';
                    const route = `{{ url('main-categories') }}/${id}/toggle`;

                    statusToggle.disabled = true;

                    fetch(route, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                        .then(async response => {
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Failed to update status.');
                            }

                            showStatusAlert('success', `Status updated to ${newStatus}.`);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            statusToggle.checked = !statusToggle.checked;
                            showStatusAlert('danger', error.message || 'Unable to update status right now.');
                        })
                        .finally(() => {
                            statusToggle.disabled = false;
                        });
                });
            });
        });
    </script>
@endpush
