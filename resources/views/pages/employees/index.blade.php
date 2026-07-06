@extends('layouts.app')

@section('title', 'Manage Users List')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

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
            <form action="{{ route('employees.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search users" value="{{ request('q') }}">
                </div>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add User</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table table-nowrap">
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
                                            <small>{{ $assignedRole ?: $employee->role }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employee->phone ?: '-' }}</td>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->designation ?: '-' }}</td>
                                <td>{{ $assignedRole ?: ($employee->role ?: '-') }}</td>
                                <td>{{ optional($employee->hire_date)->format('d M Y') ?: '-' }}</td>
                                <td><span
                                        class="badge {{ $employee->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($employee->status) }}</span>
                                </td>

                                <td class="text-end">
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                            data-bs-toggle="dropdown" aria-expanded="false"><i
                                                class="ti ti-dots-vertical"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ route('employees.show', $employee) }}"><i
                                                    class="ti ti-eye text-info"></i> View</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit_employee_{{ $employee->id }}"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <button type="button" class="dropdown-item text-danger crm-delete-trigger"
                                                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                data-delete-action="{{ route('employees.destroy', $employee) }}"
                                                data-delete-title="Delete User"
                                                data-delete-message="Are you sure you want to delete user '{{ $employee->name }}'?">
                                                <i class="ti ti-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">No users available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $users->links() }}</div>
        </div>
    </div>
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
                <div class="col-md-6"><label class="form-label">Hire Date</label><input type="date" name="hire_date"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Address</label><input type="text" name="address"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Avatar</label><input type="file" name="avatar"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Status</label><select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select></div>
                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                        data-bs-dismiss="offcanvas">Cancel</button><button type="submit" class="btn btn-primary">Save
                        User</button></div>
            </form>
        </div>
    </div>

    @foreach ($users as $employee)
        <div class="modal fade" id="edit_employee_{{ $employee->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
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
                            <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update
                                    User</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#permissions-table').DataTable({
                pageLength: 10,
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: 4 }
                ]
            });

        });
    </script>
@endpush
