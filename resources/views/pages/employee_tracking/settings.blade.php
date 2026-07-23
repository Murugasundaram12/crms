@extends('layouts.app')

@section('title', 'Tracking Settings')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tracking Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tracking.index') }}">Employee Tracking</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Settings</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('tracking.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-map-pin me-1"></i> Timeline
        </a>
    </div>

    <form action="{{ route('tracking.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            @foreach($sections as $sectionTitle => $keys)
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">{{ $sectionTitle }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach($keys as $key)
                                    <div class="col-12 {{ in_array($key, ['map_center_latitude', 'map_center_longitude', 'location_update_interval', 'location_update_interval_type', 'offline_check_time', 'offline_check_time_type'], true) ? 'col-md-6' : '' }}">
                                        @php($label = str($key)->replace('_', ' ')->title())
                                        @php($value = old($key, $settings[$key] ?? null))

                                        @if(is_bool($settings[$key] ?? null))
                                            <input type="hidden" name="{{ $key }}" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}" name="{{ $key }}" value="1" @checked((bool) $value)>
                                                <label class="form-check-label fw-semibold" for="{{ $key }}">{{ $label }}</label>
                                            </div>
                                            @error($key)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        @elseif(in_array($key, ['location_update_interval_type'], true))
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                                <option value="seconds" @selected($value === 'seconds')>Seconds</option>
                                                <option value="minutes" @selected($value === 'minutes')>Minutes</option>
                                            </select>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @elseif(in_array($key, ['offline_check_time_type'], true))
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                                <option value="seconds" @selected($value === 'seconds')>Seconds</option>
                                                <option value="minutes" @selected($value === 'minutes')>Minutes</option>
                                                <option value="hours" @selected($value === 'hours')>Hours</option>
                                            </select>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @elseif($key === 'map_provider')
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                                <option value="google" @selected($value === 'google')>Google</option>
                                                <option value="leaflet" @selected($value === 'leaflet')>Leaflet</option>
                                            </select>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @elseif($key === 'distance_unit')
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                                <option value="km" @selected($value === 'km')>KM</option>
                                                <option value="miles" @selected($value === 'miles')>Miles</option>
                                            </select>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @elseif($key === 'default_route_mode')
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                                <option value="actual" @selected($value === 'actual')>Actual GPS Route</option>
                                                <option value="road" @selected($value === 'road')>Estimated Road Route</option>
                                            </select>
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @else
                                            <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                                            <input
                                                type="number"
                                                step="{{ in_array($key, ['minimum_accuracy', 'minimum_distance_meters', 'maximum_speed_kmph', 'large_gap_minutes', 'large_gap_distance_meters', 'map_center_latitude', 'map_center_longitude'], true) ? '0.000001' : '1' }}"
                                                id="{{ $key }}"
                                                name="{{ $key }}"
                                                class="form-control @error($key) is-invalid @enderror"
                                                value="{{ $value }}"
                                            >
                                            @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body d-flex justify-content-end gap-2">
                <a href="{{ route('tracking.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Save Settings
                </button>
            </div>
        </div>
    </form>
@endsection
