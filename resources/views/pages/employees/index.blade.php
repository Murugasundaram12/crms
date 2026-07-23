@extends('layouts.app')

@section('title', 'Manage Users List')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')
    @php
        $permissionGroups = ($permissions ?? collect())->groupBy(function ($permission) {
            return str_contains($permission->key, '-')
                ? \Illuminate\Support\Str::beforeLast($permission->key, '-')
                : explode('.', $permission->key)[0];
        });
    @endphp

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Manage Users<span class="badge badge-soft-primary ms-2">{{ $users->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            @can('employees-create')
                <a href="javascript:void(0);" class="btn btn-primary shadow-sm" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add User</a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('employees.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search users" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
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
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Designation</th>
                            <th>Roles</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $employee)
                            @php
                                $assignedRole = $employee->roles->pluck('name')->join(', ');
                                $selectedRole = old('role', $employee->roles->first()?->name ?? $employee->role);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-md me-2"><img
                                                src="{{ asset($employee->avatar ?: 'assets/img/users/user-01.jpg') }}" alt="img"
                                                class="rounded-circle"></span>
                                        <div>
                                            <h6 class="mb-0 fs-14">{{ $employee->name }}</h6>
                                            <small>{{ $assignedRole ?: ($employee->role ?: '-') }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employee->phone ?: '-' }}</td>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->designation ?: '-' }}</td>
                                <td>{{ $assignedRole ?: ($employee->role ?: '-') }}</td>
                                <td>{{ optional($employee->hire_date)->format('d M Y') ?: '-' }}</td>
                                <td><span
                                        class="badge {{ $employee->status === 'active' ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary' }}">{{ $employee->status ? ucfirst($employee->status) : '-' }}</span>
                                </td>

                                <td class="text-end">
                                    <div class="table-action d-inline-flex align-items-center justify-content-end gap-1 flex-wrap">
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('employees.show', $employee) }}"><i
                                                class="ti ti-eye me-1"></i>View</a>
                                        @can('employees-edit')
                                            <a class="btn btn-sm btn-outline-primary" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_employee_{{ $employee->id }}"><i
                                                    class="ti ti-edit me-1"></i>Edit</a>
                                        @endcan
                                        @can('employees-delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger"
                                                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                data-delete-action="{{ route('employees.destroy', $employee) }}"
                                                data-delete-title="Delete User"
                                                data-delete-message="Are you sure you want to delete user '{{ $employee->name }}'?">
                                                <i class="ti ti-trash me-1"></i>Delete
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No users available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">{{ $users->withQueryString()->links() }}</div>
        @endif
    </div>
    @can('employees-create')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add User</h5><button type="button" class="btn-close"
                data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('employees.store') }}" method="POST" class="row g-3" enctype="multipart/form-data">
                @csrf
                <div class="col-12"><label class="form-label">Name</label><input type="text" name="name"
                        class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email"
                        class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Designation</label><input type="text" name="designation"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Hourly Rate</label><input type="number" step="0.01"
                        name="hourly_rate" class="form-control"></div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password"
                        class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password"
                        name="password_confirmation" class="form-control" required></div>
                <div class="col-md-6">
                    <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                    <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror" value="{{ old('hire_date') }}" required>
                    @error('hire_date')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
                    @error('address')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12"><label class="form-label">Avatar</label><input type="file" name="avatar"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Status</label><select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select></div>
                @if($permissionGroups->isNotEmpty())
                    <div class="col-12">
                        <label class="form-label">Extra User Permissions <span class="text-danger">*</span></label>
                        <p class="text-muted fs-13 mb-2">Additional permissions for this user only. Role permissions still apply. (At least one required)</p>
                        @error('direct_permissions')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        <div class="border rounded p-3 @error('direct_permissions') border-danger @enderror" style="max-height: 260px; overflow:auto;">
                            <div class="row g-3">
                                @foreach($permissionGroups as $module => $modulePermissions)
                                    <div class="col-12 col-md-6">
                                        <div class="fw-semibold mb-2">{{ \Illuminate\Support\Str::title(str_replace(['-', '_'], ' ', $module)) }}</div>
                                        @foreach($modulePermissions as $permission)
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" name="direct_permissions[]"
                                                    value="{{ $permission->id }}" id="add-direct-permission-{{ $permission->id }}"
                                                    @checked(in_array($permission->id, old('direct_permissions', [])))>
                                                <label class="form-check-label" for="add-direct-permission-{{ $permission->id }}">
                                                    {{ $permission->name }} <small class="text-muted">({{ $permission->key }})</small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                        data-bs-dismiss="offcanvas">Cancel</button><button type="submit" class="btn btn-primary">Save
                        User</button></div>
            </form>
        </div>
    </div>
    @endcan

    @can('employees-edit')
    @foreach ($users as $employee)
        <div class="modal fade" id="edit_employee_{{ $employee->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('employees.update', $employee) }}" method="POST" class="row g-3" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="col-12"><label class="form-label">Name</label><input type="text" name="name"
                                    class="form-control" value="{{ $employee->name }}" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email"
                                    class="form-control" value="{{ $employee->email }}" required></div>
                            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone"
                                    class="form-control" value="{{ $employee->phone }}"></div>
                            <div class="col-md-6"><label class="form-label">Designation</label><input type="text"
                                    name="designation" class="form-control" value="{{ $employee->designation }}"></div>
                            <div class="col-md-6"><label class="form-label">Hourly Rate</label><input type="number" step="0.01"
                                    name="hourly_rate" class="form-control" value="{{ $employee->hourly_rate }}"></div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="">-- Select Role --</option>

                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected($selectedRole == $role->name)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Password</label><input type="password"
                                    name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password"
                                    name="password_confirmation" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">Hire Date</label><input type="date" name="hire_date"
                                    class="form-control" value="{{ optional($employee->hire_date)->format('Y-m-d') }}"></div>
                            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address"
                                    class="form-control" value="{{ $employee->address }}"></div>
                            <div class="col-12"><label class="form-label">Avatar</label><input type="file" name="avatar"
                                    class="form-control"></div>
                            <div class="col-12"><label class="form-label">Status</label><select name="status"
                                    class="form-select">
                                    <option value="active" @selected($employee->status === 'active')>Active</option>
                                    <option value="inactive" @selected($employee->status === 'inactive')>Inactive</option>
                                </select></div>
                            @if($permissionGroups->isNotEmpty())
                                @php
                                    $rolePermissionIds = $employee->roles
                                        ->flatMap(fn($role) => $role->permissions->pluck('id'))
                                        ->unique()
                                        ->values()
                                        ->all();
                                    $selectedDirectPermissions = old('direct_permissions', $employee->directPermissions->pluck('id')->all());
                                @endphp
                                <div class="col-12">
                                    <label class="form-label">Permissions</label>
                                    <p class="text-muted fs-13 mb-2">Role permissions are already selected. Extra user permissions can be changed here.</p>
                                    <div class="border rounded p-3" style="max-height: 260px; overflow:auto;">
                                        <div class="row g-3">
                                            @foreach($permissionGroups as $module => $modulePermissions)
                                                <div class="col-12 col-md-6">
                                                    <div class="fw-semibold mb-2">{{ \Illuminate\Support\Str::title(str_replace(['-', '_'], ' ', $module)) }}</div>
                                                    @foreach($modulePermissions as $permission)
                                                        @php
                                                            $isRolePermission = in_array($permission->id, $rolePermissionIds);
                                                            $isDirectPermission = in_array($permission->id, $selectedDirectPermissions);
                                                        @endphp
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox"
                                                                @if(! $isRolePermission) name="direct_permissions[]" @endif
                                                                value="{{ $permission->id }}" id="edit-direct-permission-{{ $employee->id }}-{{ $permission->id }}"
                                                                @checked($isRolePermission || $isDirectPermission)
                                                                @disabled($isRolePermission)>
                                                            <label class="form-check-label" for="edit-direct-permission-{{ $employee->id }}-{{ $permission->id }}">
                                                                {{ $permission->name }} <small class="text-muted">({{ $permission->key }})</small>
                                                                @if($isRolePermission)
                                                                    <span class="badge bg-soft-primary text-primary ms-1">Role</span>
                                                                @elseif($isDirectPermission)
                                                                    <span class="badge bg-soft-info text-info ms-1">User</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update
                                    User</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    @endcan
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addForm = document.querySelector('form[action="{{ route('employees.store') }}"]');
            if (addForm) {
                addForm.addEventListener('submit', function (e) {
                    const checkedPermissions = addForm.querySelectorAll('input[name="direct_permissions[]"]:checked');
                    if (checkedPermissions.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one Extra User Permission.');
                    }
                });
            }
        });
    </script>
@endpush
