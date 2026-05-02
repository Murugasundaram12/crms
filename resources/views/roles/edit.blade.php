@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    @include('partials.alerts')

    @php($selectedPermissions = old('permissions', $rolePermissions ?? []))
    @php($grouped = $permissions->groupBy(fn ($permission) => explode('.', $permission->key)[0]))

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Edit Role</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-light shadow-sm">Back to Roles</a>
    </div>

    <form action="{{ route('roles.update', $role) }}" method="POST" id="roleForm" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Role Details</h5>
                    <p class="text-muted fs-13 mb-0">Update the basic information for this role.</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                            value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" rows="5" name="description"
                            placeholder="Explain what this role can access">{{ old('description', $role->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="rounded-3 bg-light p-3">
                        <span class="badge badge-soft-primary mb-2">{{ count($selectedPermissions) }} Selected</span>
                        <p class="text-muted mb-0 fs-13">Any sidebar item linked to these permissions will appear for assigned users.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-1">Assign Permissions</h5>
                    <p class="text-muted fs-13 mb-0">Fine-tune what {{ $role->name }} can access.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border rounded-3 bg-light px-3 py-2 mb-3">
                        <div>
                            <h6 class="mb-0">Quick Select</h6>
                            <small class="text-muted">Enable all permissions at once or module-wise.</small>
                        </div>
                        <label class="d-inline-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input permission-select-all" type="checkbox">
                            <span class="fw-medium text-dark">Select All</span>
                        </label>
                    </div>
                    <div class="row g-3">
                        @foreach($grouped as $module => $modulePermissions)
                            <div class="col-12">
                                <div class="border rounded-3 p-3 permission-module">
                                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                        <h6 class="mb-0 text-dark">{{ ucwords(str_replace('_', ' ', $module)) }}</h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-light text-dark">{{ $modulePermissions->count() }} items</span>
                                            <label class="d-inline-flex align-items-center gap-2 mb-0">
                                                <input class="form-check-input permission-module-toggle" type="checkbox">
                                                <span class="text-dark fw-medium">Select Module</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        @foreach($modulePermissions as $permission)
                                            <div class="col-md-6">
                                                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start w-100">
                                                    <input class="form-check-input mt-1 permission-checkbox" type="checkbox"
                                                        name="permissions[]" value="{{ $permission->id }}"
                                                        id="perm-{{ $permission->id }}"
                                                        @checked(in_array($permission->id, $selectedPermissions))>
                                                    <span>
                                                        <span class="d-block fw-semibold text-dark">{{ $permission->name }}</span>
                                                        <small class="text-muted">{{ $permission->key }}</small>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const allToggle = document.querySelector('.permission-select-all');
            const moduleCards = document.querySelectorAll('.permission-module');

            const syncModuleToggle = (card) => {
                const items = card.querySelectorAll('.permission-checkbox');
                const checked = card.querySelectorAll('.permission-checkbox:checked');
                const moduleToggle = card.querySelector('.permission-module-toggle');

                if (!moduleToggle || items.length === 0) {
                    return;
                }

                moduleToggle.checked = checked.length === items.length;
                moduleToggle.indeterminate = checked.length > 0 && checked.length < items.length;
            };

            const syncAllToggle = () => {
                const items = document.querySelectorAll('.permission-checkbox');
                const checked = document.querySelectorAll('.permission-checkbox:checked');

                if (!allToggle || items.length === 0) {
                    return;
                }

                allToggle.checked = checked.length === items.length;
                allToggle.indeterminate = checked.length > 0 && checked.length < items.length;
            };

            moduleCards.forEach((card) => {
                const moduleToggle = card.querySelector('.permission-module-toggle');
                const items = card.querySelectorAll('.permission-checkbox');

                if (moduleToggle) {
                    moduleToggle.addEventListener('change', function () {
                        items.forEach((item) => {
                            item.checked = moduleToggle.checked;
                        });

                        syncModuleToggle(card);
                        syncAllToggle();
                    });
                }

                items.forEach((item) => {
                    item.addEventListener('change', function () {
                        syncModuleToggle(card);
                        syncAllToggle();
                    });
                });

                syncModuleToggle(card);
            });

            if (allToggle) {
                allToggle.addEventListener('change', function () {
                    document.querySelectorAll('.permission-checkbox').forEach((item) => {
                        item.checked = allToggle.checked;
                    });

                    moduleCards.forEach((card) => syncModuleToggle(card));
                    syncAllToggle();
                });
            }

            syncAllToggle();
        });
    </script>
@endpush
