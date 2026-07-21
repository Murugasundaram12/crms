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
            snapRouteUrl: @json(route('dashboard.snapTimeLineRoute')),
            csrfToken: @json(csrf_token()),
            iconBase: @json(asset('img/map') . '/'),
            selectedEmployee: @json(request('employee')),
            selectedDate: @json(request('date')),
        };

        let timelineMap;
        let timelineMarkers = [];
        let timelinePolylines = [];
        let timelineRenderToken = 0;

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
            const renderToken = ++timelineRenderToken;

            let contents = '';
            let finalDistance = '- KM';
            const latLngs = [];
            const addressLookups = [];
            let hasTravelPoint = false;
            let attendanceCard = '';
            let visibleMarkerNumber = 0;
            let lastVisibleStop = null;

            if (items.length > 0) {
                items.forEach(function (item, index) {
                    const latitude = Number(item.latitude);
                    const longitude = Number(item.longitude);
                    const isTravelPoint = item.type === 'vehicle';
                    const isCollapsedStill = shouldCollapseStillStop(item, lastVisibleStop);
                    hasTravelPoint = hasTravelPoint || isTravelPoint;

                    if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0) {
                        const position = new google.maps.LatLng(latitude, longitude);
                        latLngs.push(position);

                        const trackingType = item.trackingType;
                        const isAttendancePoint = trackingType === 0 || trackingType === 3 || trackingType === 'checked_in' || trackingType === 'checked_out';
                        const shouldShowMarker = (isAttendancePoint || !isTravelPoint) && !isCollapsedStill;
                        const markerNumber = shouldShowMarker ? ++visibleMarkerNumber : null;

                        if (shouldShowMarker) {
                            const marker = new google.maps.Marker({
                                position,
                                map: timelineMap,
                                icon: {
                                    url: timelineConfig.iconBase + (isAttendancePoint ? 'location_pin_blue.png' : 'location_pin.png'),
                                    scaledSize: isAttendancePoint ? new google.maps.Size(34, 34) : new google.maps.Size(42, 42),
                                    labelOrigin: isAttendancePoint ? new google.maps.Point(15, 11) : new google.maps.Point(21, 22),
                                },
                                label: {
                                    text: String(markerNumber),
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
                            if (item.type === 'still') {
                                lastVisibleStop = {...item, latitude, longitude};
                            }
                        }
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

                    if (item.type === 'vehicle' || isCollapsedStill) {
                        return;
                    } else {
                        const displayNumber = visibleMarkerNumber || index + 1;
                        contents += `
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <span><i class="ti ti-clock me-1"></i>${escapeHtml(item.startTime || '-')} - ${escapeHtml(item.endTime || '-')}</span>
                                    ${batteryHtml(item.batteryPercentage)}
                                </div>
                                    <div class="d-flex justify-content-between gap-2">
                                        <h6 class="text-primary mb-1"><span class="badge bg-primary me-1">${displayNumber}</span>${escapeHtml(item.type || 'Tracking')}</h6>
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

                drawTimelineRoute(items, latLngs, buildMovementPaths(items), hasTravelPoint, renderToken);

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

        async function drawTimelineRoute(items, latLngs, movementPaths, hasTravelPoint, renderToken) {
            if (!latLngs.length) {
                return;
            }

            if (latLngs.length === 1 || !hasTravelPoint) {
                timelineMap.setCenter(latLngs[0]);
                timelineMap.setZoom(15);
                return;
            }

            const bounds = new google.maps.LatLngBounds();
            latLngs.forEach((point) => bounds.extend(point));
            timelineMap.fitBounds(bounds);

            for (const routePath of movementPaths) {
                if (renderToken !== timelineRenderToken) {
                    return;
                }

                await drawSnappedRoute(routePath, renderToken);
                await wait(120);
            }
        }

        function computeDistanceMeters(p1, p2) {
            if (!p1 || !p2) {
                return 0;
            }
            if (window.google?.maps?.geometry?.spherical?.computeDistanceBetween) {
                return google.maps.geometry.spherical.computeDistanceBetween(p1, p2);
            }
            const lat1 = typeof p1.lat === 'function' ? p1.lat() : Number(p1.lat);
            const lng1 = typeof p1.lng === 'function' ? p1.lng() : Number(p1.lng);
            const lat2 = typeof p2.lat === 'function' ? p2.lat() : Number(p2.lat);
            const lng2 = typeof p2.lng === 'function' ? p2.lng() : Number(p2.lng);

            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function filterPolylineCoordinates(latLngs, minDistanceMeters = 5) {
            if (!Array.isArray(latLngs) || latLngs.length === 0) {
                return [];
            }
            const filtered = [latLngs[0]];
            for (let i = 1; i < latLngs.length; i++) {
                const prev = filtered[filtered.length - 1];
                const curr = latLngs[i];
                if (computeDistanceMeters(prev, curr) >= minDistanceMeters) {
                    filtered.push(curr);
                }
            }
            return filtered;
        }

        function buildMovementPaths(items) {
            const paths = [];
            let currentPath = [];
            let previousPoint = null;

            items.forEach((item) => {
                const point = itemLatLng(item);

                if (!point) {
                    return;
                }

                if (item.segmentBreakBefore && currentPath.length >= 2) {
                    paths.push(currentPath);
                    currentPath = [];
                } else if (item.segmentBreakBefore) {
                    currentPath = [];
                }

                if (!previousPoint || computeDistanceMeters(previousPoint, point) >= 5) {
                    currentPath.push(point);
                    previousPoint = point;
                }
            });

            if (currentPath.length >= 2) {
                paths.push(currentPath);
            }

            return paths;
        }

        function itemLatLng(item) {
            const latitude = Number(item.latitude);
            const longitude = Number(item.longitude);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude === 0 || longitude === 0) {
                return null;
            }

            return new google.maps.LatLng(latitude, longitude);
        }

        function shouldCollapseStillStop(item, lastVisibleStop) {
            if (!lastVisibleStop || item.type !== 'still' || lastVisibleStop.type !== 'still') {
                return false;
            }

            const currentPoint = itemLatLng(item);
            const previousPoint = itemLatLng(lastVisibleStop);

            if (!currentPoint || !previousPoint) {
                return false;
            }

            return computeDistanceMeters(previousPoint, currentPoint) <= 200;
        }

        async function drawSnappedRoute(routePath, renderToken) {
            if (routePath.length < 2) {
                return;
            }

            try {
                const response = await fetch(timelineConfig.snapRouteUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': timelineConfig.csrfToken,
                    },
                    body: JSON.stringify({
                        points: routePath.map((point) => ({
                            lat: point.lat(),
                            lng: point.lng(),
                        })),
                    }),
                });

                if (renderToken !== timelineRenderToken) {
                    return;
                }

                if (!response.ok) {
                    drawRoutePolyline(routePath);
                    return;
                }

                const data = await response.json();
                const snappedPath = (data.points || [])
                    .map((point) => new google.maps.LatLng(Number(point.lat), Number(point.lng)))
                    .filter((point) => Number.isFinite(point.lat()) && Number.isFinite(point.lng()));

                drawRoutePolyline(
                    data.snapped && isSnappedRouteUsable(routePath, snappedPath) ? snappedPath : routePath
                );
            } catch (error) {
                if (renderToken !== timelineRenderToken) {
                    return;
                }

                drawRoutePolyline(routePath);
            }
        }

        function isSnappedRouteUsable(rawPath, snappedPath) {
            if (snappedPath.length < 2) {
                return false;
            }

            const rawLength = computeDistanceMeters(rawPath[0], rawPath[rawPath.length - 1]);
            const snappedLength = computeDistanceMeters(snappedPath[0], snappedPath[snappedPath.length - 1]);
            const startDrift = computeDistanceMeters(rawPath[0], snappedPath[0]);
            const endDrift = computeDistanceMeters(rawPath[rawPath.length - 1], snappedPath[snappedPath.length - 1]);

            return startDrift <= 150 && endDrift <= 150;
        }

        function drawRoutePolyline(latLngs) {
            const cleanPath = filterPolylineCoordinates(latLngs, 5);
            if (cleanPath.length < 2) {
                return;
            }

            const polyline = new google.maps.Polyline({
                path: cleanPath,
                geodesic: false,
                strokeColor: '#0000FF',
                strokeWeight: 3,
                map: timelineMap,
            });

            timelinePolylines.push(polyline);
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
            timelineRenderToken++;
            timelineMarkers.forEach(function (marker) {
                marker.setMap(null);
            });
            timelineMarkers = [];

            timelinePolylines.forEach(function (polyline) {
                polyline.setMap(null);
            });
            timelinePolylines = [];
        }

        function wait(milliseconds) {
            return new Promise((resolve) => setTimeout(resolve, milliseconds));
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
