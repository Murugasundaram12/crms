@extends('layouts.app')

@section('title', 'Live Location')
@section('content_class', 'pb-0')

@php($googleMapsKey = $mapSettings['google_maps_api_key'] ?? '')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #liveLocationMap {
            width: 100%;
            height: 80vh;
            min-height: 560px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .tracking-leaflet-label {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            border-radius: 4px;
            background: #fff;
            color: #1f1c1c;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .18);
            font-size: 14px;
            font-weight: 700;
            padding: 3px 7px;
            transform: translate(18px, -18px);
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
        <div>
            <h4 class="mb-1">Live Location</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Live Location</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group" role="group" aria-label="Live location status">
            <button type="button" class="btn btn-outline-primary">Online <span class="badge bg-success" id="onlineCount">0</span></button>
            <button type="button" class="btn btn-outline-primary">Offline <span class="badge bg-danger" id="offlineCount">0</span></button>
            <button type="button" class="btn btn-outline-secondary" id="refreshLiveMap"><i class="ti ti-refresh me-1"></i>Refresh</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
            <div id="liveLocationMap"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const liveLocationConfig = {
            centerLatitude: Number(@json($mapSettings['center_latitude'])) || 20.5937,
            centerLongitude: Number(@json($mapSettings['center_longitude'])) || 78.9629,
            zoom: Number(@json($mapSettings['zoom_level'])) || 12,
            liveUrl: @json(route('liveLocationAjax')),
            iconBase: @json(asset('img/map') . '/'),
            hasGoogleMapsKey: @json(filled($googleMapsKey)),
        };

        let liveMap;
        let liveMapProvider = null;
        let liveMarkers = [];
        let liveInfoWindows = [];

        function initLiveLocationMap() {
            if (liveMap) {
                return;
            }

            const center = {
                lat: liveLocationConfig.centerLatitude,
                lng: liveLocationConfig.centerLongitude,
            };

            if (window.google?.maps && !window.googleMapsFailed) {
                try {
                    liveMapProvider = 'google';
                    liveMap = new google.maps.Map(document.getElementById('liveLocationMap'), {
                        center: new google.maps.LatLng(center.lat, center.lng),
                        zoom: liveLocationConfig.zoom,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        scrollWheel: true,
                        gestureHandling: 'greedy',
                        streetViewControl: false,
                    });
                } catch (e) {
                    initLeafletLiveMap(center.lat, center.lng);
                }
            } else if (window.L) {
                initLeafletLiveMap(center.lat, center.lng);
            } else {
                return;
            }

            document.getElementById('refreshLiveMap')?.addEventListener('click', loadLiveLocationMap);
            loadLiveLocationMap();
        }

        function initLeafletLiveMap(lat, lng) {
            liveMapProvider = 'leaflet';
            const container = document.getElementById('liveLocationMap');
            if (!container) return;
            container.innerHTML = '';
            liveMap = L.map('liveLocationMap').setView([lat, lng], liveLocationConfig.zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(liveMap);
        }

        window.gm_authFailure = function () {
            window.googleMapsFailed = true;
            if (liveMapProvider !== 'leaflet' && document.getElementById('liveLocationMap')) {
                clearLiveMarkers();
                liveMap = null;
                initLeafletLiveMap(liveLocationConfig.centerLatitude, liveLocationConfig.centerLongitude);
                loadLiveLocationMap();
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (!liveLocationConfig.hasGoogleMapsKey) {
                initLiveLocationMap();
            } else {
                setTimeout(function () {
                    if (!liveMap) {
                        initLiveLocationMap();
                    }
                }, 2000);
            }
        });

        async function loadLiveLocationMap() {
            try {
                const response = await fetch(liveLocationConfig.liveUrl, {
                    headers: {'Accept': 'application/json'},
                });
                const payload = await response.json();
                const employees = payload.data || payload || [];
                let online = 0;
                let offline = 0;

                clearLiveMarkers();

                employees.forEach(function (employee) {
                    const latitude = Number(employee.latitude);
                    const longitude = Number(employee.longitude);

                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                        return;
                    }

                    const isOnline = (employee.status || employee.online_status) === 'online';
                    const name = employee.name || employee.employee_name || employee.employee?.name || 'Employee';

                    isOnline ? online++ : offline++;

                    if (liveMapProvider === 'google') {
                        addGoogleLiveMarker(employee, name, latitude, longitude, isOnline);
                    } else {
                        addLeafletLiveMarker(employee, name, latitude, longitude, isOnline);
                    }
                });

                document.getElementById('onlineCount').textContent = online;
                document.getElementById('offlineCount').textContent = offline;
            } catch (e) {
                console.error('Failed loading live locations', e);
            }
        }

        function addGoogleLiveMarker(employee, name, latitude, longitude, isOnline) {
            const markerIcon = {
                url: liveLocationConfig.iconBase + (isOnline ? 'green_circle.png' : 'red_circle.png'),
                scaledSize: new google.maps.Size(32, 32),
                labelOrigin: new google.maps.Point(20, -10),
            };

            const marker = new google.maps.Marker({
                position: new google.maps.LatLng(latitude, longitude),
                icon: markerIcon,
                map: liveMap,
                label: {
                    text: name,
                    color: '#1F1C1C',
                    fontWeight: 'bold',
                    fontSize: '16px',
                    className: 'card p-1',
                },
                draggable: false,
            });

            const infoWindow = new google.maps.InfoWindow({maxWidth: 220});
            marker.addListener('click', function () {
                infoWindow.setContent(livePopup(employee));
                infoWindow.open(liveMap, marker);
            });

            liveMarkers.push(marker);
            liveInfoWindows.push(infoWindow);
        }

        function addLeafletLiveMarker(employee, name, latitude, longitude, isOnline) {
            const iconUrl = liveLocationConfig.iconBase + (isOnline ? 'green_circle.png' : 'red_circle.png');
            const icon = L.divIcon({
                className: '',
                html: `<img src="${iconUrl}" width="32" height="32" alt=""><span class="tracking-leaflet-label">${escapeHtml(name)}</span>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });

            const marker = L.marker([latitude, longitude], {icon, title: name})
                .addTo(liveMap)
                .bindPopup(livePopup(employee));

            liveMarkers.push(marker);
        }

        function clearLiveMarkers() {
            liveInfoWindows.forEach(function (infoWindow) {
                infoWindow.close();
            });
            liveInfoWindows = [];

            liveMarkers.forEach(function (marker) {
                if (liveMapProvider === 'google') {
                    marker.setMap(null);
                    return;
                }

                if (marker.remove) {
                    marker.remove();
                }
            });
            liveMarkers = [];
        }

        function livePopup(employee) {
            return `
                <div style="min-width:200px">
                    <strong>${escapeHtml(employee.name || employee.employee_name || employee.employee?.name || 'Employee')}</strong>
                    <div>Status: ${escapeHtml(employee.status || employee.online_status || '-')}</div>
                    <div>Battery: ${employee.battery_percentage ?? '-'}%</div>
                    <div>GPS: ${employee.is_gps_on ? 'On' : 'Off'}</div>
                    <div>Last Update: ${employee.updatedAt || (employee.last_seen_at ? new Date(employee.last_seen_at).toLocaleString() : '-')}</div>
                </div>
            `;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char];
            });
        }
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @if (filled($googleMapsKey))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&callback=initLiveLocationMap&v=weekly" async defer onerror="window.gm_authFailure()"></script>
    @endif
@endpush
