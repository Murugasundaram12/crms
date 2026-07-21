@extends('layouts.app')

@section('title', 'Device Management')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Device Management<span class="badge badge-soft-primary ms-2">{{ $devices->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Device Management</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Registered Devices</span><h4 class="mb-0">{{ $summary['registered'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Logged In</span><h4 class="mb-0">{{ $summary['logged_in'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Logged Out</span><h4 class="mb-0">{{ $summary['logged_out'] }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm"><div class="card-body"><span class="text-muted">Online</span><h4 class="mb-0">{{ $summary['online'] }}</h4></div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('device-management.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Employee / email / phone / device" value="{{ request('q') }}">
                </div>
                <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('device-management.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
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
                            <th>Employee</th>
                            <th>Device</th>
                            <th>Login Status</th>
                            <th>Online Status</th>
                            <th>GPS</th>
                            <th>Battery</th>
                            <th>Last Seen</th>
                            <th>Location</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $device->employee?->name ?? 'Unknown' }}</div>
                                    <div class="text-muted small">{{ $device->employee?->email ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $device->device_name ?: 'Mobile Device' }}</div>
                                    <div class="text-muted small">{{ $device->device_id }}</div>
                                    @if($device->brand || $device->model || $device->device_type)
                                        <div class="text-muted small">
                                            {{ collect([$device->brand, $device->model, $device->device_type])->filter()->implode(' / ') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $device->login_status === 'login' ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary' }}">
                                        {{ $device->login_status === 'login' ? 'Login' : 'Logout' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $device->online_status === 'online' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                        {{ ucfirst($device->online_status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($device->is_mock_location)
                                        <span class="badge bg-soft-danger text-danger">Mock</span>
                                    @elseif($device->is_gps_on)
                                        <span class="badge bg-soft-success text-success">On</span>
                                    @else
                                        <span class="badge bg-soft-warning text-warning">Off</span>
                                    @endif
                                </td>
                                <td>{{ $device->battery_percentage !== null ? $device->battery_percentage . '%' : '-' }}</td>
                                <td>
                                    <div>{{ $device->last_seen_at?->format('d M Y h:i A') ?? '-' }}</div>
                                    <div class="text-muted small">{{ $device->last_seen_at?->diffForHumans() ?? 'No update yet' }}</div>
                                </td>
                                <td>
                                    @if($device->latitude !== null && $device->longitude !== null)
                                        {{ number_format((float) $device->latitude, 6) }}, {{ number_format((float) $device->longitude, 6) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#crmDeleteModal"
                                        data-delete-action="{{ route('device-management.destroy', $device) }}"
                                        data-delete-title="Delete Device"
                                        data-delete-message="Are you sure you want to delete device '{{ $device->device_name ?: $device->device_id }}'? The employee must register the device again from mobile app."
                                        title="Delete" aria-label="Delete device">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No registered devices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($devices->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $devices->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
