@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Clients<span class="badge badge-soft-primary ms-2">{{ $clients->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clients</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <form action="{{ route('clients.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search clients"
                        value="{{ request('q') }}">
                </div>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
            @can('clients-create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Clients</a>
            @endcan
        </div>
    </div>

    <div class="row">
        @forelse ($clients as $client)
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card border shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" class="avatar avatar-md flex-shrink-0 me-2">
                                    <img src="{{ asset('assets/img/profiles/avatar-0' . (($loop->iteration % 7) + 1) . '.jpg') }}"
                                        alt="img" class="rounded-circle">
                                </a>
                                <div>
                                    <h6 class="fs-14"><a href="javascript:void(0);" class="fw-medium">{{ $client->name }}</a>
                                    </h6>
                                    <p class="text-default mb-0">{{ $client->company_name ?: 'Construction Client' }}</p>
                                </div>
                            </div>
                            <div class="dropdown table-action">
                                <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    @can('clients-edit')
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#edit_client_{{ $client->id }}"><i class="ti ti-edit text-blue"></i>
                                            Edit</a>
                                    @endcan
                                    @can('clients-delete')
                                    <button type="button" class="dropdown-item crm-delete-trigger" data-bs-toggle="modal"
                                        data-bs-target="#crmDeleteModal"
                                        data-delete-action="{{ route('clients.destroy', $client) }}"
                                        data-delete-title="Delete Client"
                                        data-delete-message="Are you sure you want to delete client '{{ $client->name }}'?">
                                        <i class="ti ti-trash"></i> Delete
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        <div class="d-block">
                            <div class="d-flex flex-column">
                                <p class="text-default d-inline-flex align-items-center mb-2"><i
                                        class="ti ti-mail text-dark me-1"></i>{{ $client->email ?: '-' }}</p>
                                <p class="text-default d-inline-flex align-items-center mb-2"><i
                                        class="ti ti-phone text-dark me-1"></i>{{ $client->phone ?: '-' }}</p>
                                <p class="text-default d-inline-flex align-items-center"><i
                                        class="ti ti-map-pin-pin text-dark me-1"></i>{{ $client->country ?: '-' }}</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-tag badge-soft-success me-2">{{ ucfirst($client->status) }}</span>
                                <span class="badge badge-tag badge-soft-warning">Projects: {{ $client->projects_count }}</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <div class="d-flex align-items-center grid-social-links">
                                <a href="javascript:void(0);" class="avatar avatar-xs text-dark rounded-circle me-1"><i
                                        class="ti ti-building fs-14"></i></a>
                                <a href="javascript:void(0);" class="avatar avatar-xs text-dark rounded-circle me-1"><i
                                        class="ti ti-report-money fs-14"></i></a>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-light text-dark">Payments: {{ $client->payments_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">No clients added yet</h5>
                        <p class="text-muted mb-3">Create your first client to start managing projects.</p>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvas_add">Add Clients</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="load-btn text-center mt-3">
        {{ $clients->links() }}
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('clients.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12"><label class="form-label">Name</label><input type="text" name="name"
                        class="form-control" required></div>
                <div class="col-12"><label class="form-label">Company</label><input type="text" name="company_name"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone"
                        class="form-control"></div>
                <div class="col-12"><label class="form-label">Address</label><input type="text" name="address"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">State</label><input type="text" name="state"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Country</label><input type="text" name="country"
                        value="india" class="form-control"></div>
                <div class="col-12"><label class="form-label">Status</label><select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control"
                        rows="4"></textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                        data-bs-dismiss="offcanvas">Cancel</button><button type="submit" class="btn btn-primary">Save
                        Client</button></div>
            </form>
        </div>
    </div>

    @foreach ($clients as $client)
        <div class="modal fade" id="edit_client_{{ $client->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Client</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('clients.update', $client) }}" method="POST" class="row g-3">
                            @csrf
                            @method('PUT')
                            <div class="col-12"><label class="form-label">Name</label><input type="text" name="name"
                                    class="form-control" value="{{ $client->name }}" required></div>
                            <div class="col-12"><label class="form-label">Company</label><input type="text" name="company_name"
                                    class="form-control" value="{{ $client->company_name }}"></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email"
                                    class="form-control" value="{{ $client->email }}"></div>
                            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone"
                                    class="form-control" value="{{ $client->phone }}"></div>
                            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address"
                                    class="form-control" value="{{ $client->address }}"></div>
                            <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city"
                                    class="form-control" value="{{ $client->city }}"></div>
                            <div class="col-md-4"><label class="form-label">State</label><input type="text" name="state"
                                    class="form-control" value="{{ $client->state }}"></div>
                            <div class="col-md-4"><label class="form-label">Country</label><input type="text" name="country"
                                    class="form-control" value="{{ $client->country }}"></div>
                            <div class="col-12"><label class="form-label">Status</label><select name="status"
                                    class="form-select">
                                    <option value="active" @selected($client->status === 'active')>Active</option>
                                    <option value="inactive" @selected($client->status === 'inactive')>Inactive</option>
                                </select></div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes"
                                    class="form-control" rows="4">{{ $client->notes }}</textarea></div>
                            <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                                    data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update
                                    Client</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
@endpush
