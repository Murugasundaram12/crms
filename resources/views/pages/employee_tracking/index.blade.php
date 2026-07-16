@extends('layouts.app')

@section('title', 'Timeline')
@section('content_class', 'pb-0')

@php($googleMapsKey = $mapSettings['google_maps_api_key'] ?? '')

@push('styles')
    <style>
        .timeline-map-wrapper {
            position: relative;
            width: 100%;
            height: 80vh;
            min-height: 640px;
        }

        #employeeTrackingMap {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            background: #eef2f7;
        }

        #timelineOverlayCard {
            position: absolute;
            top: 96px;
            left: 28px;
            width: min(420px, calc(100% - 56px));
            max-height: calc(100% - 128px);
            overflow: auto;
            z-index: 5;
        }

        .timeline-travel-label {
            display: block;
            margin: 8px 0;
            padding: 8px 10px;
            border-radius: 6px;
            background: #16a34a;
            color: #fff;
            font-weight: 700;
        }

        .timeline-summary-card {
            background: #ef1d0d;
            border-color: #ef1d0d;
            border-radius: 5px;
            overflow: hidden;
            padding: 0;
        }

        .timeline-summary-card .card-body {
            padding: 24px 28px;
        }

        .timeline-summary-card dt,
        .timeline-summary-card dd {
            margin-bottom: 12px;
            line-height: 1.35;
        }

        .timeline-summary-card dt {
            padding-right: 14px;
        }

        .timeline-summary-card .card-header {
            background: transparent;
            border-bottom-color: rgba(255, 255, 255, .65);
            color: #fff;
        }

        .timeline-summary-card .card-body {
            color: #fff;
        }

        .attendance-session-card {
            border-radius: 6px;
        }

        .attendance-session-card .session-heading {
            color: #ef1d0d;
            font-weight: 700;
        }

        .attendance-session-card .session-section {
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid #e8edf5;
        }

        .attendance-session-card .session-label {
            color: #5f6b7a;
            font-size: 13px;
            font-weight: 700;
        }

        .attendance-session-card .session-address {
            color: #606b7a;
            font-size: 13px;
            line-height: 1.45;
        }

        .attendance-session-card .session-meta {
            color: #778292;
            font-size: 12px;
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .timeline-map-wrapper {
                min-height: 560px;
            }

            #timelineOverlayCard {
                top: auto;
                bottom: 16px;
                left: 16px;
                width: calc(100% - 32px);
                max-height: 42%;
            }
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
        <h4 class="mb-0">Timeline</h4>
        <div class="d-flex gap-3 flex-wrap">
            <input type="date" id="trackingDate" class="form-control form-control-sm" value="{{ request('date', now()->toDateString()) }}" style="width: 150px;">
            <select id="trackingEmployee" class="form-select form-select-sm" style="width: 220px;">
                <option value="">Please select employee</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) request('employee') === (string) $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if (blank($googleMapsKey))
        <div class="alert alert-warning">
            Google Maps API key missing. Field project timeline map uses Google Maps; add <code>google_maps_api_key</code> in app settings to match it exactly.
        </div>
    @endif

    <div class="timeline-map-wrapper shadow-sm">
        <div id="employeeTrackingMap"></div>
        <div id="timelineOverlayCard"></div>
    </div>
@endsection

@push('scripts')
    <script>
        const timelineConfig = {
            centerLatitude: Number(@json($mapSettings['center_latitude'])),
            centerLongitude: Number(@json($mapSettings['center_longitude'])),
            zoom: Number(@json($mapSettings['zoom_level'])),
            timelineUrl: @json(route('dashboard.getTimeLineAjax')),
            csrfToken: @json(csrf_token()),
            iconBase: @json(asset('img/map') . '/'),
            selectedEmployee: @json(request('employee')),
            selectedDate: @json(request('date')),
        };

        let timelineMap;
        let timelineMarkers = [];
        let timelinePolyline = null;

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('trackingEmployee')?.addEventListener('change', loadTimelineData);
            document.getElementById('trackingDate')?.addEventListener('change', loadTimelineData);
        });

        function initEmployeeTrackingMap() {
            const center = new google.maps.LatLng(timelineConfig.centerLatitude, timelineConfig.centerLongitude);

            timelineMap = new google.maps.Map(document.getElementById('employeeTrackingMap'), {
                zoom: timelineConfig.zoom,
                center,
                scrollWheel: true,
                draggable: true,
                mapTypeControlOptions: {
                    mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.HYBRID],
                },
                streetViewControl: false,
                scaleControl: true,
                zoomControl: true,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                gestureHandling: 'greedy',
            });

            if (document.getElementById('trackingEmployee').value && document.getElementById('trackingDate').value) {
                loadTimelineData();
            }
        }

        async function loadTimelineData() {
            if (!timelineMap) {
                return;
            }

            const userId = document.getElementById('trackingEmployee').value;
            const date = document.getElementById('trackingDate').value;

            if (!userId || !date) {
                return;
            }

            const body = new URLSearchParams();
            body.set('userId', userId);
            body.set('date', date);
            body.set('_token', timelineConfig.csrfToken);

            const response = await fetch(timelineConfig.timelineUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': timelineConfig.csrfToken,
                },
                body,
            });

            if (!response.ok) {
                document.getElementById('timelineOverlayCard').innerHTML = overlayShell('Unable to load timeline.');
                return;
            }

            renderTimeline(await response.json());
        }

        function renderTimeline(data) {
            const items = data.timeLineItems || [];
            clearTimelineMap();

            let contents = '';
            let finalDistance = '- KM';
            const latLngs = [];
            const addressLookups = [];
            let hasTravelPoint = false;
            let attendanceCard = '';

            if (items.length > 0) {
                items.forEach(function (item, index) {
                    const latitude = Number(item.latitude);
                    const longitude = Number(item.longitude);
                    const isTravelPoint = item.type === 'vehicle';
                    hasTravelPoint = hasTravelPoint || isTravelPoint;

                    if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0) {
                        const position = new google.maps.LatLng(latitude, longitude);
                        latLngs.push(position);

                        const trackingType = item.trackingType;
                        const markerIcon = {
                            url: timelineConfig.iconBase + (trackingType === 0 || trackingType === 3 || trackingType === 'checked_in' || trackingType === 'checked_out'
                                ? 'location_pin_blue.png'
                                : 'location_pin.png'),
                            scaledSize: trackingType === 0 || trackingType === 3 || trackingType === 'checked_in' || trackingType === 'checked_out'
                                ? new google.maps.Size(34, 34)
                                : new google.maps.Size(42, 42),
                            labelOrigin: trackingType === 0 || trackingType === 3 || trackingType === 'checked_in' || trackingType === 'checked_out'
                                ? new google.maps.Point(15, 11)
                                : new google.maps.Point(21, 22),
                        };

                        const marker = new google.maps.Marker({
                            position,
                            map: timelineMap,
                            icon: markerIcon,
                            label: {
                                text: String(index + 1),
                                color: '#1F1C1C',
                                fontWeight: 'bold',
                                fontSize: '16px',
                                className: 'card p-0',
                            },
                            draggable: false,
                        });

                        marker.addListener('click', function () {
                            timelineMap.setZoom(15);
                            timelineMap.setCenter(position);
                        });

                        timelineMarkers.push(marker);
                    }

                    const addressId = `timelineAddress${index}`;
                    const address = item.address
                        ? `${escapeHtml(item.address)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${latitude}, ${longitude})">View in map</a>`
                        : 'Unknown address!';

                    if (!item.address && Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0) {
                        addressLookups.push({id: addressId, latitude, longitude});
                    }

                    if (item.type === 'checkIn' || item.type === 'checkOut') {
                        return;
                    }

                    if (item.type === 'vehicle') {
                        contents += `<span class="timeline-travel-label">Travel ${escapeHtml(item.startTime || '-')} - ${escapeHtml(item.endTime || '-')} (${escapeHtml(item.elapseTime || '00:00:00')}H) ${escapeHtml(item.distance ?? 0)} KM</span>`;
                    } else {
                        contents += `
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <span><i class="ti ti-clock me-1"></i>${escapeHtml(item.startTime || '-')} - ${escapeHtml(item.endTime || '-')}</span>
                                    ${batteryHtml(item.batteryPercentage)}
                                </div>
                                    <div class="d-flex justify-content-between gap-2">
                                        <h6 class="text-primary mb-1"><span class="badge bg-primary me-1">${index + 1}</span>${escapeHtml(item.type || 'Tracking')}</h6>
                                        <span class="small">${accuracyHtml(item.accuracy)}</span>
                                    </div>
                                    <div class="small text-muted" id="${addressId}">${address}</div>
                                </div>
                            </div>
                        `;
                    }
                });

                attendanceCard = attendanceSessionCard(items);
                contents = `${attendanceCard}${contents}`;

                drawTimelineRoute(items, latLngs, hasTravelPoint);

                if (data.totalKM !== undefined && data.totalKM !== null) {
                    finalDistance = `${Number(data.totalKM).toFixed(2)} KM`;
                } else if (hasTravelPoint && latLngs.length) {
                    finalDistance = `${Math.round((google.maps.geometry.spherical.computeLength(latLngs) / 1000) * 100) / 100} KM`;
                } else {
                    finalDistance = '0.00 KM';
                }
            } else {
                contents = '<p class="text-muted mb-0">No data!</p>';
            }

            document.getElementById('timelineOverlayCard').innerHTML = overlayShell(contents, data, finalDistance);
            const distanceNode = document.getElementById('timelineDistance');
            if (distanceNode) {
                distanceNode.textContent = finalDistance;
            }
            resolveMissingAddresses(addressLookups);
        }

        function attendanceSessionCard(items) {
            const checkInIndex = items.findIndex((item) => item.type === 'checkIn');
            const checkOutIndex = items.findIndex((item) => item.type === 'checkOut');
            const checkInItem = checkInIndex >= 0 ? items[checkInIndex] : null;
            const checkOutItem = checkOutIndex >= 0 ? items[checkOutIndex] : null;

            if (!checkInItem && !checkOutItem) {
                return '';
            }

            const startTime = checkInItem?.startTime || '-';
            const endTime = checkOutItem?.startTime || checkOutItem?.endTime || '-';
            const batteryValue = checkOutItem?.batteryPercentage ?? checkInItem?.batteryPercentage;
            const checkInAccuracy = accuracyHtml(checkInItem?.accuracy);
            const checkOutAccuracy = accuracyHtml(checkOutItem?.accuracy);

            const checkInAddress = checkInItem
                ? `<div class="session-address" id="timelineAddress${checkInIndex}">${itemAddressHtml(checkInItem)}</div>`
                : '<div class="session-address">-</div>';

            const checkOutAddress = checkOutItem
                ? `<div class="session-address" id="timelineAddress${checkOutIndex}">${itemAddressHtml(checkOutItem)}</div>`
                : '<div class="session-address">Not checked out</div>';

            return `
                <div class="card attendance-session-card mb-2 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <span><i class="ti ti-clock me-1"></i>${escapeHtml(startTime)} - ${escapeHtml(endTime)}</span>
                            ${batteryHtml(batteryValue)}
                        </div>
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <h6 class="session-heading mb-0"><span class="badge bg-primary me-1">1</span>Attendance</h6>
                            <span class="small">Session</span>
                        </div>
                        <div class="session-section">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <div class="session-label">Check In ${escapeHtml(checkInItem?.startTime || '-')}</div>
                                <div class="session-meta">${checkInAccuracy}</div>
                            </div>
                            ${checkInAddress}
                        </div>
                        <div class="session-section">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <div class="session-label">Check Out ${escapeHtml(checkOutItem?.startTime || '-')}</div>
                                <div class="session-meta">${checkOutAccuracy}</div>
                            </div>
                            ${checkOutAddress}
                        </div>
                    </div>
                </div>
            `;
        }

        function itemAddressHtml(item) {
            const latitude = Number(item.latitude);
            const longitude = Number(item.longitude);

            if (item.address) {
                return `${escapeHtml(item.address)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${latitude}, ${longitude})">View in map</a>`;
            }

            return 'Unknown address!';
        }

        function drawTimelineRoute(items, latLngs, hasTravelPoint) {
            if (!latLngs.length) {
                return;
            }

            if (latLngs.length === 1 || !hasTravelPoint) {
                timelineMap.setCenter(latLngs[0]);
                timelineMap.setZoom(15);
                return;
            }

            const middle = items[Math.round((items.length - 1) / 2)];
            timelineMap.setCenter(new google.maps.LatLng(Number(middle.latitude), Number(middle.longitude)));
            timelineMap.setZoom(11);

            timelinePolyline = new google.maps.Polyline({
                path: latLngs,
                geodesic: true,
                strokeColor: '#0000FF',
                strokeWeight: 3,
                map: timelineMap,
            });
        }

        function resolveMissingAddresses(addressLookups) {
            if (!addressLookups.length || !window.google?.maps?.Geocoder) {
                return;
            }

            const geocoder = new google.maps.Geocoder();
            addressLookups.forEach(function (lookup, index) {
                setTimeout(function () {
                    geocoder.geocode({
                        location: {lat: lookup.latitude, lng: lookup.longitude},
                    }, function (results, status) {
                        const node = document.getElementById(lookup.id);
                        if (!node) {
                            return;
                        }

                        if (status === 'OK' && results && results[0]) {
                            node.innerHTML = `${escapeHtml(results[0].formatted_address)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${lookup.latitude}, ${lookup.longitude})">View in map</a>`;
                            return;
                        }

                        node.textContent = 'Address not found';
                    });
                }, index * 250);
            });
        }

        function overlayShell(contents, data = {}, distance = '-') {
            return `
                <div class="bg-white rounded shadow-lg p-3">
                    <div class="timeline-summary-card mb-3 shadow-lg">
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-6">Total tracked time</dt>
                                <dd class="col-6">${escapeHtml(data.totalTrackedTime || '00:00:00')}</dd>
                                <dt class="col-6">Total attendance time</dt>
                                <dd class="col-6">${escapeHtml(data.totalAttendanceTime || '00:00:00')}</dd>
                                <dt class="col-6">Total travelled distance</dt>
                                <dd class="col-6" id="timelineDistance">${escapeHtml(distance)}</dd>
                                <dt class="col-6">Device information</dt>
                                <dd class="col-6">${escapeHtml(data.deviceInfo || '-')}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="timeline mt-1" style="max-height:350px; overflow-y:auto;">${contents}</div>
                </div>
            `;
        }

        function clearTimelineMap() {
            timelineMarkers.forEach(function (marker) {
                marker.setMap(null);
            });
            timelineMarkers = [];

            if (timelinePolyline) {
                timelinePolyline.setMap(null);
                timelinePolyline = null;
            }
        }

        function focusTimelinePoint(latitude, longitude) {
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
            }
            timelineMap.setZoom(18);
            timelineMap.setCenter({lat: latitude, lng: longitude});
        }

        function batteryHtml(value) {
            if (value === null || value === undefined) {
                return '<span class="text-muted"><i class="ti ti-battery me-1"></i>-%</span>';
            }

            const color = value >= 40 ? 'success' : (value >= 15 ? 'warning' : 'danger');
            return `<span class="text-${color}"><i class="ti ti-battery me-1"></i>${escapeHtml(value)}%</span>`;
        }

        function accuracyHtml(value) {
            if (value === null || value === undefined || value === '') {
                return 'Accuracy -';
            }

            return `Accuracy ${escapeHtml(value)}m`;
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

    @if (filled($googleMapsKey))
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=geometry&callback=initEmployeeTrackingMap&v=weekly"></script>
    @endif
@endpush
