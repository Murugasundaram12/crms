@extends('layouts.app')

@section('title', 'Clients')

@section('content')
@include('partials.alerts')

@if (session('client_import_result'))
@php
    $importResult = session('client_import_result');
@endphp
@if (!empty($importResult['failed']) || !empty($importResult['errors']))
    <div class="alert alert-warning border-0 shadow-sm">
        <div class="fw-semibold mb-1">Client import {{ str_replace('_', ' ', $importResult['status']) }}</div>
        <div>Imported: {{ $importResult['imported'] }} &nbsp; Updated: {{ $importResult['updated'] }} &nbsp; Skipped:
            {{ $importResult['skipped'] }} &nbsp; Failed: {{ $importResult['failed'] }}</div>
        @if ($importResult['errors'])
            <details class="mt-2">
                <summary>Show row issues</summary>
                <ul class="mb-0 mt-2">@foreach ($importResult['errors'] as $error)
                <li>Row {{ $error['row'] ?? '-' }}: {{ $error['message'] }}</li>@endforeach
                </ul>
            </details>
        @endif
    </div>
@else
    @php
        $clientToastMsg = 'Client import ' . str_replace('_', ' ', $importResult['status'] ?? 'completed') . ' — Imported: ' . ($importResult['imported'] ?? 0) . ', Updated: ' . ($importResult['updated'] ?? 0) . ', Skipped: ' . ($importResult['skipped'] ?? 0) . ', Failed: ' . ($importResult['failed'] ?? 0);
    @endphp
    <script>
        window.__crmQueuedToasts = window.__crmQueuedToasts || [];
        window.__crmQueuedToasts.push(@json($clientToastMsg));
    </script>
@endif
@endif

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
        @can('clients-create')
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Clients</a>
            <a href="#clientImportModal" data-bs-toggle="modal" class="btn btn-outline-primary">Import</a>
        @endcan
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <form action="{{ route('clients.index') }}" method="GET" class="row g-3 align-items-end m-0">
            <div class="col-12 col-lg-4">
                <label class="form-label">Search</label>
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search clients"
                        value="{{ request('q') }}">
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="enquiry" @selected(request('status') === 'enquiry')>Enquiry</option>
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
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @forelse ($clients as $client)
        <div class="col-xxl-3 col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 client-grid-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3 client-card-head">
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
                        <div class="client-card-actions table-action d-inline-flex align-items-center gap-1">
                            @can('clients-edit')
                                <a class="btn btn-sm btn-outline-primary" href="#" data-bs-toggle="modal"
                                    data-bs-target="#edit_client_{{ $client->id }}" title="Edit" aria-label="Edit client">
                                    <i class="ti ti-edit"></i>
                                </a>
                            @endcan
                            @can('clients-delete')
                                <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger"
                                    data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                    data-delete-action="{{ route('clients.destroy', $client) }}"
                                    data-delete-title="Delete Client"
                                    data-delete-message="Are you sure you want to delete client '{{ $client->name }}'?"
                                    title="Delete" aria-label="Delete client">
                                    <i class="ti ti-trash"></i>
                                </button>
                            @endcan
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
                    @can('clients-create')
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvas_add">Add Clients</a>
                    @endcan
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="load-btn text-center mt-3">
    {{ $clients->links() }}
</div>

@can('clients-create')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('clients.store') }}" method="POST" class="row g-3" novalidate>
                @csrf
                <input type="hidden" name="_modal_target" value="#offcanvas_add">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" @error('name') aria-describedby="client_name_error" aria-invalid="true"
                        @enderror required>
                    @error('name')
                    <div class="invalid-feedback d-block" id="client_name_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Company</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                        value="{{ old('company_name') }}" @error('company_name')
                        aria-describedby="client_company_name_error" aria-invalid="true" @enderror>
                    @error('company_name')
                    <div class="invalid-feedback d-block" id="client_company_name_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" @error('email') aria-describedby="client_email_error" aria-invalid="true"
                        @enderror>
                    @error('email')
                    <div class="invalid-feedback d-block" id="client_email_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone') }}" @error('phone') aria-describedby="client_phone_error" aria-invalid="true"
                        @enderror required>
                    @error('phone')
                    <div class="invalid-feedback d-block" id="client_phone_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                        value="{{ old('address') }}" @error('address') aria-describedby="client_address_error"
                        aria-invalid="true" @enderror>
                    @error('address')
                    <div class="invalid-feedback d-block" id="client_address_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                        value="{{ old('city') }}" @error('city') aria-describedby="client_city_error" aria-invalid="true"
                        @enderror>
                    @error('city')
                    <div class="invalid-feedback d-block" id="client_city_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
                        value="{{ old('state') }}" @error('state') aria-describedby="client_state_error" aria-invalid="true"
                        @enderror>
                    @error('state')
                    <div class="invalid-feedback d-block" id="client_state_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" value="{{ old('country', 'India') }}"
                        class="form-control @error('country') is-invalid @enderror" @error('country')
                        aria-describedby="client_country_error" aria-invalid="true" @enderror>
                    @error('country')
                    <div class="invalid-feedback d-block" id="client_country_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" @error('status')
                    aria-describedby="client_status_error" aria-invalid="true" @enderror>
                        <option value="enquiry" @selected(old('status') === 'enquiry')>Enquiry</option>
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                    @error('status')
                    <div class="invalid-feedback d-block" id="client_status_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4"
                        @error('notes') aria-describedby="client_notes_error" aria-invalid="true"
                        @enderror>{{ old('notes') }}</textarea>
                    @error('notes')
                    <div class="invalid-feedback d-block" id="client_notes_error">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Client</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@can('clients-edit')
    @foreach ($clients as $client)
        <div class="modal fade" id="edit_client_{{ $client->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Client</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('clients.update', $client) }}" method="POST" class="row g-3" novalidate>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_modal_target" value="#edit_client_{{ $client->id }}">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $client->name) }}" @error('name')
                                    aria-describedby="edit_client_{{ $client->id }}_name_error" aria-invalid="true" @enderror
                                    required>
                                @error('name')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_name_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Company</label>
                                <input type="text" name="company_name"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    value="{{ old('company_name', $client->company_name) }}" @error('company_name')
                                        aria-describedby="edit_client_{{ $client->id }}_company_name_error" aria-invalid="true"
                                    @enderror>
                                @error('company_name')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_company_name_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $client->email) }}" @error('email')
                                    aria-describedby="edit_client_{{ $client->id }}_email_error" aria-invalid="true" @enderror>
                                @error('email')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_email_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $client->phone) }}" @error('phone')
                                    aria-describedby="edit_client_{{ $client->id }}_phone_error" aria-invalid="true" @enderror
                                    required>
                                @error('phone')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_phone_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                    value="{{ old('address', $client->address) }}" @error('address')
                                        aria-describedby="edit_client_{{ $client->id }}_address_error" aria-invalid="true"
                                    @enderror>
                                @error('address')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_address_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                    value="{{ old('city', $client->city) }}" @error('city')
                                    aria-describedby="edit_client_{{ $client->id }}_city_error" aria-invalid="true" @enderror>
                                @error('city')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_city_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control @error('state') is-invalid @enderror"
                                    value="{{ old('state', $client->state) }}" @error('state')
                                    aria-describedby="edit_client_{{ $client->id }}_state_error" aria-invalid="true" @enderror>
                                @error('state')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_state_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control @error('country') is-invalid @enderror"
                                    value="{{ old('country', $client->country) }}" @error('country')
                                        aria-describedby="edit_client_{{ $client->id }}_country_error" aria-invalid="true"
                                    @enderror>
                                @error('country')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_country_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" @error('status')
                                aria-describedby="edit_client_{{ $client->id }}_status_error" aria-invalid="true" @enderror>
                                    <option value="enquiry" @selected(old('status', $client->status) === 'enquiry')>Enquiry
                                    </option>
                                    <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_status_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4"
                                    @error('notes') aria-describedby="edit_client_{{ $client->id }}_notes_error"
                                    aria-invalid="true" @enderror>{{ old('notes', $client->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block" id="edit_client_{{ $client->id }}_notes_error">
                                {{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Client</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endcan

@can('clients-create')
    <div class="modal fade" id="clientImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Import Clients</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('clients.import') }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        <input type="hidden" name="_modal_target" value="#clientImportModal">
                        <label class="form-label">Excel / CSV file <span class="text-danger">*</span></label>
                        <input class="form-control mb-2 @error('file') is-invalid @enderror" type="file" name="file"
                            accept=".xlsx,.xls,.csv" @error('file') aria-describedby="client_import_file_error"
                            aria-invalid="true" @enderror required>
                        @error('file')
                            <div class="invalid-feedback d-block mb-2" id="client_import_file_error">{{ $message }}</div>
                        @enderror
                        <div class="form-text mb-3">Required columns: Name, Phone and Status. Empty status defaults to
                            Active.</div>
                        <a href="{{ route('clients.import.sample') }}" class="btn btn-link px-0">Download sample Excel</a>
                        <div class="d-flex justify-content-end gap-2 mt-3"><button type="button" class="btn btn-light"
                                data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Import</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan

@if ($errors->any() && old('_modal_target'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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