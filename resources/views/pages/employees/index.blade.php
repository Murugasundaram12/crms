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
                @include('partials.excel-import-modal', ['module'=>'employees','action'=>route('employees.import'),'sampleUrl'=>route('excel.import.sample','employees'),'title'=>'Employees'])
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
                                            @if(! $employee->isSuperAdmin() && auth()->id() !== $employee->id)
                                                <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger"
                                                    data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                    data-delete-action="{{ route('employees.destroy', $employee) }}"
                                                    data-delete-title="Delete User"
                                                    data-delete-message="Are you sure you want to delete user '{{ $employee->name }}'?">
                                                    <i class="ti ti-trash me-1"></i>Delete
                                                </button>
                                            @endif
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
            <h5 class="offcanvas-title">Add User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('employees.store') }}" method="POST" class="row g-3" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="_modal_target" value="#offcanvas_add">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" @error('name') aria-describedby="employee_name_error" aria-invalid="true" @enderror required>
                    @error('name')<div class="invalid-feedback d-block" id="employee_name_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" @error('email') aria-describedby="employee_email_error" aria-invalid="true" @enderror required>
                    @error('email')<div class="invalid-feedback d-block" id="employee_email_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" @error('phone') aria-describedby="employee_phone_error" aria-invalid="true" @enderror>
                    @error('phone')<div class="invalid-feedback d-block" id="employee_phone_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation') }}" @error('designation') aria-describedby="employee_designation_error" aria-invalid="true" @enderror>
                    @error('designation')<div class="invalid-feedback d-block" id="employee_designation_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hourly Rate</label>
                    <input type="number" step="0.01" name="hourly_rate" class="form-control @error('hourly_rate') is-invalid @enderror" value="{{ old('hourly_rate') }}" @error('hourly_rate') aria-describedby="employee_hourly_rate_error" aria-invalid="true" @enderror>
                    @error('hourly_rate')<div class="invalid-feedback d-block" id="employee_hourly_rate_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" @error('role') aria-describedby="employee_role_error" aria-invalid="true" @enderror required>
                        <option value="">-- Select Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback d-block" id="employee_role_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" @error('password') aria-describedby="employee_password_error" aria-invalid="true" @enderror required>
                    @error('password')<div class="invalid-feedback d-block" id="employee_password_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" @error('password_confirmation') aria-describedby="employee_password_confirmation_error" aria-invalid="true" @enderror required>
                    @error('password_confirmation')<div class="invalid-feedback d-block" id="employee_password_confirmation_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                    <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror" value="{{ old('hire_date') }}" @error('hire_date') aria-describedby="employee_hire_date_error" aria-invalid="true" @enderror required>
                    @error('hire_date')<div class="invalid-feedback d-block" id="employee_hire_date_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" @error('address') aria-describedby="employee_address_error" aria-invalid="true" @enderror required>
                    @error('address')<div class="invalid-feedback d-block" id="employee_address_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Avatar</label>
                    <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" @error('avatar') aria-describedby="employee_avatar_error" aria-invalid="true" @enderror>
                    @error('avatar')<div class="invalid-feedback d-block" id="employee_avatar_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" @error('status') aria-describedby="employee_status_error" aria-invalid="true" @enderror>
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<div class="invalid-feedback d-block" id="employee_status_error">{{ $message }}</div>@enderror
                </div>
                @if($permissionGroups->isNotEmpty())
                    <div class="col-12">
                        <label class="form-label">Extra User Permissions <span class="text-danger">*</span></label>
                        <p class="text-muted fs-13 mb-2">Additional permissions for this user only. Role permissions still apply. (At least one required)</p>
                        @error('direct_permissions')
                            <div class="invalid-feedback d-block mb-2" id="employee_direct_permissions_error">{{ $message }}</div>
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
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
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
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('employees.update', $employee) }}" method="POST" class="row g-3" enctype="multipart/form-data" novalidate>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_modal_target" value="#edit_employee_{{ $employee->id }}">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" @error('name') aria-describedby="edit_employee_{{ $employee->id }}_name_error" aria-invalid="true" @enderror required>
                                @error('name')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_name_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email) }}" @error('email') aria-describedby="edit_employee_{{ $employee->id }}_email_error" aria-invalid="true" @enderror required>
                                @error('email')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_email_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" @error('phone') aria-describedby="edit_employee_{{ $employee->id }}_phone_error" aria-invalid="true" @enderror>
                                @error('phone')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_phone_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation) }}" @error('designation') aria-describedby="edit_employee_{{ $employee->id }}_designation_error" aria-invalid="true" @enderror>
                                @error('designation')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_designation_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hourly Rate</label>
                                <input type="number" step="0.01" name="hourly_rate" class="form-control @error('hourly_rate') is-invalid @enderror" value="{{ old('hourly_rate', $employee->hourly_rate) }}" @error('hourly_rate') aria-describedby="edit_employee_{{ $employee->id }}_hourly_rate_error" aria-invalid="true" @enderror>
                                @error('hourly_rate')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_hourly_rate_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" @error('role') aria-describedby="edit_employee_{{ $employee->id }}_role_error" aria-invalid="true" @enderror required>
                                    <option value="">-- Select Role --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected(old('role', $selectedRole) == $role->name)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_role_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave blank to keep current password" @error('password') aria-describedby="edit_employee_{{ $employee->id }}_password_error" aria-invalid="true" @enderror>
                                @error('password')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_password_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" @error('password_confirmation') aria-describedby="edit_employee_{{ $employee->id }}_password_confirmation_error" aria-invalid="true" @enderror>
                                @error('password_confirmation')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_password_confirmation_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hire Date <span class="text-danger">*</span></label>
                                <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror" value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}" @error('hire_date') aria-describedby="edit_employee_{{ $employee->id }}_hire_date_error" aria-invalid="true" @enderror>
                                @error('hire_date')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_hire_date_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $employee->address) }}" @error('address') aria-describedby="edit_employee_{{ $employee->id }}_address_error" aria-invalid="true" @enderror>
                                @error('address')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_address_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Avatar</label>
                                <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" @error('avatar') aria-describedby="edit_employee_{{ $employee->id }}_avatar_error" aria-invalid="true" @enderror>
                                @error('avatar')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_avatar_error">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" @error('status') aria-describedby="edit_employee_{{ $employee->id }}_status_error" aria-invalid="true" @enderror>
                                    <option value="active" @selected(old('status', $employee->status) === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>Inactive</option>
                                </select>
                                @error('status')<div class="invalid-feedback d-block" id="edit_employee_{{ $employee->id }}_status_error">{{ $message }}</div>@enderror
                            </div>
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
                                    @error('direct_permissions')
                                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                    @enderror
                                    <div class="border rounded p-3 @error('direct_permissions') border-danger @enderror" style="max-height: 260px; overflow:auto;">
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
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    @endcan

    @if ($errors->any() && old('_modal_target'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var target = @json(old('_modal_target'));
                var el = document.querySelector(target);
                if (el) {
                    if (el.classList.contains('modal')) {
                        (new bootstrap.Modal(el)).show();
                    } else if (el.classList.contains('offcanvas')) {
                        (new bootstrap.Offcanvas(el)).show();
                    }
                }
            });
        </script>
    @endif
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
