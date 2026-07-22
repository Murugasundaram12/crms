@extends('layouts.app')

@section('title', 'Timeline')
@section('content_class', 'pb-0')

@php($googleMapsKey = $mapSettings['google_maps_api_key'] ?? '')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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

        .timeline-leaflet-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ef1d0d;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .3);
            border: 2px solid #fff;
        }

        .timeline-leaflet-pin::after {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fff;
        }

        .timeline-leaflet-pin.attendance-pin {
            background: #0d6efd;
        }

        .timeline-leaflet-pin.route-start-pin {
            background: #16a34a;
        }

        .timeline-leaflet-pin.route-end-pin {
            background: #ef1d0d;
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
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-1"></i> Google Maps API key missing. Using OpenStreetMap (Leaflet) view. Add <code>google_maps_api_key</code> in app settings for Google Maps.
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
            centerLatitude: Number(@json($mapSettings['center_latitude'])) || 20.5937,
            centerLongitude: Number(@json($mapSettings['center_longitude'])) || 78.9629,
            zoom: Number(@json($mapSettings['zoom_level'])) || 12,
            timelineUrl: @json(route('dashboard.getTimeLineAjax')),
            csrfToken: @json(csrf_token()),
            iconBase: @json(asset('img/map') . '/'),
            selectedEmployee: @json(request('employee')),
            selectedDate: @json(request('date')),
            hasGoogleMapsKey: @json(filled($googleMapsKey)),
            gpsDebug: @json(request()->boolean('gps_debug')),
            defaultRouteMode: @json(request('route_mode', 'road')),
        };

        let timelineMap;
        let timelineMapProvider = null;
        let timelineMarkers = [];
        let timelinePolylines = [];
        let timelineDirectionsRenderers = [];
        let timelineRenderToken = 0;
        let lastTimelinePayload = null;

        function initEmployeeTrackingMap() {
            if (timelineMap) {
                return;
            }

            const centerLat = timelineConfig.centerLatitude;
            const centerLng = timelineConfig.centerLongitude;
            const zoomLevel = timelineConfig.zoom;

            if (window.google?.maps && !window.googleMapsFailed) {
                try {
                    timelineMapProvider = 'google';
                    timelineMap = new google.maps.Map(document.getElementById('employeeTrackingMap'), {
                        zoom: zoomLevel,
                        center: new google.maps.LatLng(centerLat, centerLng),
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
                } catch (e) {
                    initLeafletTimelineMap(centerLat, centerLng, zoomLevel);
                }
            } else if (window.L) {
                initLeafletTimelineMap(centerLat, centerLng, zoomLevel);
            }

            if (document.getElementById('trackingEmployee')?.value && document.getElementById('trackingDate')?.value) {
                loadTimelineData();
            }
        }

        function initLeafletTimelineMap(lat, lng, zoom) {
            timelineMapProvider = 'leaflet';
            const container = document.getElementById('employeeTrackingMap');
            if (!container) return;
            container.innerHTML = '';
            timelineMap = L.map('employeeTrackingMap').setView([lat, lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(timelineMap);
        }

        window.gm_authFailure = function () {
            window.googleMapsFailed = true;
            if (timelineMapProvider !== 'leaflet' && document.getElementById('employeeTrackingMap')) {
                clearTimelineMap();
                timelineMap = null;
                initLeafletTimelineMap(timelineConfig.centerLatitude, timelineConfig.centerLongitude, timelineConfig.zoom);
                if (document.getElementById('trackingEmployee')?.value && document.getElementById('trackingDate')?.value) {
                    loadTimelineData();
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('trackingEmployee')?.addEventListener('change', loadTimelineData);
            document.getElementById('trackingDate')?.addEventListener('change', loadTimelineData);

            if (!timelineConfig.hasGoogleMapsKey) {
                initEmployeeTrackingMap();
            } else {
                setTimeout(function () {
                    if (!timelineMap) {
                        initEmployeeTrackingMap();
                    }
                }, 2000);
            }
        });

        async function loadTimelineData() {
            if (!timelineMap) {
                initEmployeeTrackingMap();
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
            if (timelineConfig.gpsDebug) {
                body.set('gps_debug', '1');
                console.info('[EmployeeTracking] timeline request', {
                    url: timelineConfig.timelineUrl,
                    userId,
                    date,
                    routeMode: currentRouteMode(),
                });
            }

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

            const payload = await response.json();
            if (timelineConfig.gpsDebug) {
                console.info('[EmployeeTracking] timeline response', payload);
            }

            renderTimeline(payload);
        }

        function renderTimeline(data) {
            const items = data.timeLineItems || [];
            lastTimelinePayload = data;
            clearTimelineMap();
            const renderToken = ++timelineRenderToken;

            let contents = '';
            let finalDistance = '- KM';
            let gpsDistance = data.gpsDistanceKm ?? data.totalKM ?? null;
            let directionsDistance = data.directionsDistanceKm ?? null;
            const latLngs = [];
            const rawPoints = [];
            const addressLookups = [];
            let hasTravelPoint = false;
            let attendanceCard = '';
            let visibleMarkerNumber = 0;
            let lastVisibleStop = null;

            if (items.length > 0) {
                const routedPointIds = routePointIdsFromSegments(data.polylineSegments);
                items.forEach(function (item, index) {
                    const latitude = Number(item.latitude);
                    const longitude = Number(item.longitude);
                    const isMovementPoint = item.type === 'vehicle' || item.type === 'walk';
                    const isCollapsedStill = shouldCollapseStillStop(item, lastVisibleStop);
                    hasTravelPoint = hasTravelPoint || isMovementPoint;

                    if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0) {
                        const position = timelineMapProvider === 'google'
                            ? new google.maps.LatLng(latitude, longitude)
                            : {lat: latitude, lng: longitude};

                        latLngs.push(position);
                        rawPoints.push({lat: latitude, lng: longitude});

                        const trackingType = item.trackingType;
                        const isAttendancePoint = trackingType === 0 || trackingType === 3 || trackingType === 'checked_in' || trackingType === 'checked_out';
                        const isUnroutedMovementPoint = isMovementPoint && !routedPointIds.has(Number(item.id));
                        const shouldShowMarker = (isAttendancePoint || !isMovementPoint || items.length === 1 || isUnroutedMovementPoint) && !isCollapsedStill;
                        const markerNumber = shouldShowMarker ? ++visibleMarkerNumber : null;

                        if (shouldShowMarker) {
                            if (timelineMapProvider === 'google') {
                                const marker = new google.maps.Marker({
                                    position,
                                    map: timelineMap,
                                    icon: {
                                        url: timelineConfig.iconBase + (isAttendancePoint ? 'location_pin_blue.png' : 'location_pin.png'),
                                        scaledSize: isAttendancePoint ? new google.maps.Size(34, 34) : new google.maps.Size(42, 42),
                                    },
                                    draggable: false,
                                });

                                marker.addListener('click', function () {
                                    focusTimelinePoint(latitude, longitude);
                                });
                                timelineMarkers.push(marker);
                            } else if (timelineMapProvider === 'leaflet') {
                                const icon = L.divIcon({
                                    className: '',
                                    html: `<div class="timeline-leaflet-pin ${isAttendancePoint ? 'attendance-pin' : ''}"></div>`,
                                    iconSize: [32, 32],
                                    iconAnchor: [16, 16],
                                });
                                const marker = L.marker([latitude, longitude], {icon, title: item.type || 'Tracking'})
                                    .addTo(timelineMap);
                                marker.on('click', function () {
                                    focusTimelinePoint(latitude, longitude);
                                });
                                timelineMarkers.push(marker);
                            }

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

                    if (isMovementPoint || isCollapsedStill) {
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

                attendanceCard = attendanceSessionCard(items, data);
                contents = `${attendanceCard}${contents}`;

                const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);
                const roadSegments = data.directionsSegments || [];

                drawTimelineRoute(data, items, latLngs, rawPoints, movementPaths, roadSegments, hasTravelPoint, renderToken);

                if (gpsDistance !== undefined && gpsDistance !== null) {
                    finalDistance = `${Number(gpsDistance).toFixed(2)} KM`;
                } else if (hasTravelPoint && rawPoints.length > 1) {
                    let totalMeters = 0;
                    for (let i = 0; i < rawPoints.length - 1; i++) {
                        totalMeters += computeDistanceMeters(rawPoints[i], rawPoints[i + 1]);
                    }
                    finalDistance = `${(totalMeters / 1000).toFixed(2)} KM`;
                } else {
                    finalDistance = '0.00 KM';
                }
            } else {
                contents = '<p class="text-muted mb-0">No data!</p>';
            }

            document.getElementById('timelineOverlayCard').innerHTML = overlayShell(contents, data, finalDistance, directionsDistance);
            updateDistanceDisplay(finalDistance, directionsDistance);
            resolveMissingAddresses(addressLookups);
        }

        function attendanceSessionCard(items, data = {}) {
            const checkInIndex = items.findIndex((item) => item.type === 'checkIn');
            const checkOutIndex = items.findIndex((item) => item.type === 'checkOut');
            const checkInItem = checkInIndex >= 0 ? items[checkInIndex] : null;
            const checkOutItem = checkOutIndex >= 0 ? items[checkOutIndex] : null;
            const attendance = (data.attendanceSessions?.[0]?.attendance || data.attendances?.[0] || {});
            const firstTracking = items[0] || null;
            const lastTracking = items.length ? items[items.length - 1] : null;

            if (!checkInItem && !checkOutItem && !attendance.id) {
                return '';
            }

            const startTime = checkInItem?.startTime || attendance.check_in_time || '-';
            const endTime = checkOutItem?.startTime || checkOutItem?.endTime || attendance.check_out_time || '-';
            const batteryValue = checkOutItem?.batteryPercentage ?? lastTracking?.batteryPercentage ?? checkInItem?.batteryPercentage ?? firstTracking?.batteryPercentage;
            const checkInAccuracy = accuracyHtml(checkInItem?.accuracy ?? firstTracking?.accuracy);
            const checkOutAccuracy = accuracyHtml(checkOutItem?.accuracy ?? lastTracking?.accuracy);
            const checkInBattery = batteryHtml(checkInItem?.batteryPercentage ?? firstTracking?.batteryPercentage);
            const checkOutBattery = batteryHtml(checkOutItem?.batteryPercentage ?? lastTracking?.batteryPercentage);

            const checkInAddress = checkInItem
                ? `<div class="session-address" id="timelineAddress${checkInIndex}">${itemAddressHtml(checkInItem)}</div>`
                : '<div class="session-address text-muted">Attendance check-in time saved. Check-in GPS marker not available.</div>';

            const checkOutAddress = checkOutItem
                ? `<div class="session-address" id="timelineAddress${checkOutIndex}">${itemAddressHtml(checkOutItem)}</div>`
                : '<div class="session-address text-muted">Attendance check-out time saved. Check-out GPS marker not available.</div>';

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
                                <div class="session-label">Check In ${escapeHtml(startTime)}</div>
                                <div class="session-meta">${checkInAccuracy}</div>
                            </div>
                            <div class="session-meta mb-1">${checkInBattery}</div>
                            ${checkInAddress}
                        </div>
                        <div class="session-section">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <div class="session-label">Check Out ${escapeHtml(endTime)}</div>
                                <div class="session-meta">${checkOutAccuracy}</div>
                            </div>
                            <div class="session-meta mb-1">${checkOutBattery}</div>
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

        async function drawTimelineRoute(data, items, latLngs, rawPoints, movementPaths, directionsSegments, hasTravelPoint, renderToken) {
            if (!rawPoints.length) {
                return;
            }

            if (rawPoints.length === 1 || !hasTravelPoint) {
                focusTimelinePoint(rawPoints[0].lat, rawPoints[0].lng);
                return;
            }

            if (timelineMapProvider === 'google') {
                const bounds = new google.maps.LatLngBounds();
                rawPoints.forEach((p) => bounds.extend(new google.maps.LatLng(p.lat, p.lng)));
                timelineMap.fitBounds(bounds);
            } else if (timelineMapProvider === 'leaflet') {
                const coords = rawPoints.map((p) => [p.lat, p.lng]);
                timelineMap.fitBounds(L.latLngBounds(coords));
            }

            const routeMode = currentRouteMode(data);
            if (timelineConfig.gpsDebug) {
                console.info('[EmployeeTracking] route render plan', {
                    routeMode,
                    gpsSegments: movementPaths.length,
                    gpsVertices: movementPaths.reduce((count, segment) => count + segment.length, 0),
                    directionsSegments: Array.isArray(directionsSegments) ? directionsSegments.length : 0,
                    directionsWaypoints: Array.isArray(directionsSegments)
                        ? directionsSegments.map((segment) => (segment.waypoints || []).length)
                        : [],
                });
            }

            movementPaths.forEach(drawRouteBoundaryMarkers);

            if (routeMode === 'road' && timelineMapProvider === 'google') {
                const directionsDistanceKm = await drawDirectionsSegments(directionsSegments, renderToken);
                if (directionsDistanceKm !== null) {
                    updateDistanceDisplay(null, directionsDistanceKm);
                }
                return;
            }

            for (const routePath of movementPaths) {
                if (renderToken !== timelineRenderToken) {
                    return;
                }

                drawRoutePolyline(routePath);
                await wait(20);
            }
        }

        function currentRouteMode(data = lastTimelinePayload) {
            return timelineMapProvider === 'google' && isRoadRouteReliable(data) ? 'road' : 'actual';
        }

        function isRoadRouteReliable(data = {}) {
            const health = data?.trackingHealth || {};
            const coverage = Number(health.tracking_coverage_percentage);
            const gapCount = Number(health.gap_count);

            if (Number.isFinite(coverage) && coverage < 60) {
                return false;
            }

            if (Number.isFinite(gapCount) && gapCount > 0) {
                return false;
            }

            return true;
        }

        function lowConfidenceRouteWarning(data = {}) {
            const health = data?.trackingHealth || {};
            const coverage = Number(health.tracking_coverage_percentage);
            const gapCount = Number(health.gap_count);

            if ((Number.isFinite(coverage) && coverage < 60) || (Number.isFinite(gapCount) && gapCount > 0)) {
                return `
                    <div class="alert alert-warning py-2 px-3 mb-3 small">
                        Low tracking coverage. Estimated road route is hidden because sparse GPS points can create wrong loops or jumps.
                    </div>
                `;
            }

            return '';
        }

        function computeDistanceMeters(p1, p2) {
            if (!p1 || !p2) {
                return 0;
            }
            if (window.google?.maps?.geometry?.spherical?.computeDistanceBetween && timelineMapProvider === 'google') {
                const g1 = typeof p1.lat === 'function' ? p1 : new google.maps.LatLng(p1.lat, p1.lng);
                const g2 = typeof p2.lat === 'function' ? p2 : new google.maps.LatLng(p2.lat, p2.lng);
                return google.maps.geometry.spherical.computeDistanceBetween(g1, g2);
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

        function buildMovementPathsFromSegments(segments) {
            if (!Array.isArray(segments)) {
                return [];
            }

            return segments
                .map((segment) => {
                    const points = Array.isArray(segment) ? segment : segment?.points;
                    return Array.isArray(points)
                        ? filterPolylineCoordinates(points.map((point) => itemLatLng(point)).filter(Boolean), 3)
                        : [];
                })
                .filter((segment) => segment.length >= 2);
        }

        function routePointIdsFromSegments(segments) {
            const ids = new Set();
            if (!Array.isArray(segments)) {
                return ids;
            }

            segments.forEach((segment) => {
                const points = Array.isArray(segment) ? segment : segment?.points;
                if (!Array.isArray(points)) {
                    return;
                }

                points.forEach((point) => {
                    const id = Number(point?.id);
                    if (Number.isFinite(id)) {
                        ids.add(id);
                    }
                });
            });

            return ids;
        }

        function isRouteMovementPoint(item) {
            return item && (item.type === 'vehicle' || item.type === 'walk');
        }

        function itemLatLng(item) {
            const latitude = Number(item.latitude ?? item.lat);
            const longitude = Number(item.longitude ?? item.lng);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude === 0 || longitude === 0) {
                return null;
            }

            return {lat: latitude, lng: longitude};
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

        function drawRoutePolyline(latLngs) {
            const cleanPath = filterPolylineCoordinates(latLngs, 3);
            if (cleanPath.length < 2) {
                return;
            }

            if (isLikelyStationaryDriftPath(cleanPath)) {
                return;
            }

            if (timelineMapProvider === 'google') {
                const gPath = cleanPath.map((p) => new google.maps.LatLng(p.lat, p.lng));
                const polyline = new google.maps.Polyline({
                    path: gPath,
                    geodesic: false,
                    strokeColor: '#0000FF',
                    strokeWeight: 3,
                    map: timelineMap,
                });
                timelinePolylines.push(polyline);
            } else if (timelineMapProvider === 'leaflet') {
                const coords = cleanPath.map((p) => [p.lat, p.lng]);
                const polyline = L.polyline(coords, {
                    color: '#0000FF',
                    weight: 3,
                }).addTo(timelineMap);
                timelinePolylines.push(polyline);
            }
        }

        function drawRouteBoundaryMarkers(latLngs) {
            const cleanPath = filterPolylineCoordinates(latLngs, 3);
            if (cleanPath.length < 2) {
                return;
            }

            drawRouteBoundaryMarker(cleanPath[0], 'start');
            drawRouteBoundaryMarker(cleanPath[cleanPath.length - 1], 'end');
        }

        function drawRouteBoundaryMarker(point, boundaryType) {
            const lat = Number(point?.lat);
            const lng = Number(point?.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const title = boundaryType === 'start' ? 'Route start' : 'Route end';
            if (timelineMapProvider === 'google') {
                const marker = new google.maps.Marker({
                    position: new google.maps.LatLng(lat, lng),
                    map: timelineMap,
                    icon: {
                        url: timelineConfig.iconBase + (boundaryType === 'start' ? 'green_circle.png' : 'red_circle.png'),
                        scaledSize: new google.maps.Size(20, 20),
                        anchor: new google.maps.Point(10, 10),
                    },
                    title,
                    draggable: false,
                });
                marker.addListener('click', function () {
                    focusTimelinePoint(lat, lng);
                });
                timelineMarkers.push(marker);
                return;
            }

            if (timelineMapProvider === 'leaflet') {
                const icon = L.divIcon({
                    className: '',
                    html: `<div class="timeline-leaflet-pin route-${boundaryType}-pin"></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                });
                const marker = L.marker([lat, lng], {icon, title}).addTo(timelineMap);
                marker.on('click', function () {
                    focusTimelinePoint(lat, lng);
                });
                timelineMarkers.push(marker);
            }
        }

        async function drawDirectionsSegments(segments, renderToken) {
            if (!Array.isArray(segments) || !window.google?.maps?.DirectionsService || !window.google?.maps?.DirectionsRenderer) {
                return null;
            }

            let totalDistanceMeters = 0;
            for (const segment of segments) {
                if (renderToken !== timelineRenderToken) {
                    return null;
                }

                totalDistanceMeters += await drawDirectionsSegment(segment, renderToken);
                await wait(50);
            }

            return totalDistanceMeters > 0 ? totalDistanceMeters / 1000 : null;
        }

        function drawDirectionsSegment(segment, renderToken) {
            return new Promise((resolve) => {
                const origin = itemLatLng(segment?.origin);
                const destination = itemLatLng(segment?.destination);

                if (!origin || !destination) {
                    resolve(0);
                    return;
                }

                const waypoints = Array.isArray(segment.waypoints)
                    ? segment.waypoints
                        .map((point) => itemLatLng(point))
                        .filter(Boolean)
                        .map((point) => ({
                            location: new google.maps.LatLng(point.lat, point.lng),
                            stopover: false,
                        }))
                    : [];

                const service = new google.maps.DirectionsService();
                const renderer = new google.maps.DirectionsRenderer({
                    map: timelineMap,
                    preserveViewport: true,
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: '#0000FF',
                        strokeWeight: 3,
                    },
                });

                service.route({
                    origin: new google.maps.LatLng(origin.lat, origin.lng),
                    destination: new google.maps.LatLng(destination.lat, destination.lng),
                    waypoints,
                    optimizeWaypoints: false,
                    travelMode: google.maps.TravelMode[segment.travel_mode || 'DRIVING'] || google.maps.TravelMode.DRIVING,
                }, (result, status) => {
                    if (renderToken !== timelineRenderToken) {
                        renderer.setMap(null);
                        resolve(0);
                        return;
                    }

                    const isOk = status === 'OK' || status === google.maps.DirectionsStatus?.OK;
                    if (!isOk || !result?.routes?.length) {
                        renderer.setMap(null);
                        if (timelineConfig.gpsDebug) {
                            console.info('[EmployeeTracking] directions segment skipped', {
                                segmentNumber: segment.segment_number,
                                status,
                                sourcePointIds: segment.source_point_ids || [],
                            });
                        }
                        resolve(0);
                        return;
                    }

                    renderer.setDirections(result);
                    timelineDirectionsRenderers.push(renderer);
                    const directionsDistanceMeters = result.routes[0].legs.reduce((total, leg) => total + Number(leg.distance?.value || 0), 0);

                    if (timelineConfig.gpsDebug) {
                        console.info('[EmployeeTracking] directions segment drawn', {
                            segmentNumber: segment.segment_number,
                            travelMode: segment.travel_mode,
                            sourcePointIds: segment.source_point_ids || [],
                            origin,
                            destination,
                            waypointCount: waypoints.length,
                            directionsDistanceKm: directionsDistanceMeters / 1000,
                        });
                    }

                    resolve(directionsDistanceMeters);
                });
            });
        }

        function isLikelyStationaryDriftPath(path) {
            if (!Array.isArray(path) || path.length < 4) {
                return false;
            }

            let pathLength = 0;
            let lat = 0;
            let lng = 0;

            path.forEach((point, index) => {
                lat += Number(point.lat);
                lng += Number(point.lng);
                if (index > 0) {
                    pathLength += computeDistanceMeters(path[index - 1], point);
                }
            });

            const center = {lat: lat / path.length, lng: lng / path.length};
            const directDistance = computeDistanceMeters(path[0], path[path.length - 1]);
            const detourRatio = pathLength / Math.max(1, directDistance);
            const maxRadius = Math.max(...path.map((point) => computeDistanceMeters(center, point)));

            return pathLength >= 200 && directDistance <= 120 && detourRatio >= 4 && maxRadius <= 120;
        }

        function resolveMissingAddresses(addressLookups) {
            if (!addressLookups.length) {
                return;
            }

            if (timelineMapProvider === 'google' && window.google?.maps?.Geocoder) {
                const geocoder = new google.maps.Geocoder();
                addressLookups.forEach(function (lookup, index) {
                    setTimeout(function () {
                        geocoder.geocode({
                            location: {lat: lookup.latitude, lng: lookup.longitude},
                        }, function (results, status) {
                            const node = document.getElementById(lookup.id);
                            if (!node) return;

                            if (status === 'OK' && results && results[0]) {
                                node.innerHTML = `${escapeHtml(results[0].formatted_address)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${lookup.latitude}, ${lookup.longitude})">View in map</a>`;
                                return;
                            }

                            node.textContent = 'Address not found';
                        });
                    }, index * 250);
                });
            } else {
                addressLookups.forEach(function (lookup, index) {
                    setTimeout(async function () {
                        const node = document.getElementById(lookup.id);
                        if (!node) return;
                        try {
                            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lookup.latitude}&lon=${lookup.longitude}`);
                            if (res.ok) {
                                const data = await res.json();
                                if (data.display_name) {
                                    node.innerHTML = `${escapeHtml(data.display_name)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${lookup.latitude}, ${lookup.longitude})">View in map</a>`;
                                    return;
                                }
                            }
                        } catch (e) {}
                        node.textContent = 'Address not found';
                    }, index * 400);
                });
            }
        }

        function overlayShell(contents, data = {}, gpsDistance = '-', directionsDistance = null) {
            const routeModeLabel = currentRouteMode(data) === 'road' ? 'Estimated Road Route' : 'Actual GPS Route';
            const roadDistance = directionsDistance !== null && directionsDistance !== undefined
                ? `${Number(directionsDistance).toFixed(2)} KM`
                : '-';
            const health = data.trackingHealth || {};
            const coverage = health.tracking_coverage_percentage !== undefined
                ? `${Number(health.tracking_coverage_percentage).toFixed(2)}%`
                : '-';
            const missingDuration = health.missing_tracking_duration || '-';
            const gapCount = health.gap_count !== undefined ? health.gap_count : '-';
            const attendance = data.attendanceSessions?.[0]?.attendance || data.attendances?.[0] || {};
            const stats = timelineItemStats(data.timeLineItems || []);

            return `
                <div class="bg-white rounded shadow-lg p-3">
                    ${lowConfidenceRouteWarning(data)}
                    <div class="timeline-summary-card mb-3 shadow-lg">
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-6">Route mode</dt>
                                <dd class="col-6" id="timelineRouteModeLabel">${escapeHtml(routeModeLabel)}</dd>
                                <dt class="col-6">Total tracked time</dt>
                                <dd class="col-6">${escapeHtml(data.totalTrackedTime || '00:00:00')}</dd>
                                <dt class="col-6">Total attendance time</dt>
                                <dd class="col-6">${escapeHtml(data.totalAttendanceTime || '00:00:00')}</dd>
                                <dt class="col-6">Check-in time</dt>
                                <dd class="col-6">${escapeHtml(attendance.check_in_time || '-')}</dd>
                                <dt class="col-6">Check-out time</dt>
                                <dd class="col-6">${escapeHtml(attendance.check_out_time || '-')}</dd>
                                <dt class="col-6">Tracking accuracy</dt>
                                <dd class="col-6">${escapeHtml(stats.accuracyLabel)}</dd>
                                <dt class="col-6">Battery</dt>
                                <dd class="col-6">${escapeHtml(stats.batteryLabel)}</dd>
                                <dt class="col-6">Actual GPS distance</dt>
                                <dd class="col-6" id="timelineGpsDistance">${escapeHtml(gpsDistance)}</dd>
                                <dt class="col-6">Estimated road distance</dt>
                                <dd class="col-6" id="timelineDirectionsDistance">${escapeHtml(roadDistance)}</dd>
                                <dt class="col-6">Tracking coverage</dt>
                                <dd class="col-6">${escapeHtml(coverage)}</dd>
                                <dt class="col-6">Missing tracking time</dt>
                                <dd class="col-6">${escapeHtml(missingDuration)}</dd>
                                <dt class="col-6">Large gaps</dt>
                                <dd class="col-6">${escapeHtml(gapCount)}</dd>
                                <dt class="col-6">Device information</dt>
                                <dd class="col-6">${escapeHtml(data.deviceInfo || '-')}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="timeline mt-1" style="max-height:350px; overflow-y:auto;">${contents}</div>
                </div>
            `;
        }

        function timelineItemStats(items) {
            const validAccuracy = items
                .map((item) => Number(item.accuracy))
                .filter((value) => Number.isFinite(value));
            const validBattery = items
                .map((item) => Number(item.batteryPercentage))
                .filter((value) => Number.isFinite(value));

            const accuracyLabel = validAccuracy.length
                ? `${Math.min(...validAccuracy).toFixed(0)}-${Math.max(...validAccuracy).toFixed(0)}m`
                : '-';
            const batteryLabel = validBattery.length
                ? `${validBattery[0]}% - ${validBattery[validBattery.length - 1]}%`
                : '-';

            return {accuracyLabel, batteryLabel};
        }

        function updateDistanceDisplay(gpsDistance = null, directionsDistance = null) {
            const gpsNode = document.getElementById('timelineGpsDistance');
            if (gpsNode && gpsDistance !== null) {
                gpsNode.textContent = gpsDistance;
            }

            const directionsNode = document.getElementById('timelineDirectionsDistance');
            if (directionsNode && directionsDistance !== null && directionsDistance !== undefined) {
                directionsNode.textContent = `${Number(directionsDistance).toFixed(2)} KM`;
            }

            const routeModeNode = document.getElementById('timelineRouteModeLabel');
            if (routeModeNode) {
                routeModeNode.textContent = currentRouteMode(lastTimelinePayload) === 'road' ? 'Estimated Road Route' : 'Actual GPS Route';
            }
        }

        function clearTimelineMap() {
            timelineRenderToken++;
            timelineMarkers.forEach(function (marker) {
                if (timelineMapProvider === 'google') {
                    marker.setMap(null);
                } else if (marker.remove) {
                    marker.remove();
                }
            });
            timelineMarkers = [];

            timelinePolylines.forEach(function (polyline) {
                if (timelineMapProvider === 'google') {
                    polyline.setMap(null);
                } else if (polyline.remove) {
                    polyline.remove();
                }
            });
            timelinePolylines = [];

            timelineDirectionsRenderers.forEach(function (renderer) {
                renderer.setMap(null);
            });
            timelineDirectionsRenderers = [];
        }

        function wait(milliseconds) {
            return new Promise((resolve) => setTimeout(resolve, milliseconds));
        }

        function focusTimelinePoint(latitude, longitude) {
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                return;
            }
            if (timelineMapProvider === 'google' && timelineMap?.setZoom) {
                timelineMap.setZoom(18);
                timelineMap.setCenter({lat: latitude, lng: longitude});
            } else if (timelineMapProvider === 'leaflet' && timelineMap?.setView) {
                timelineMap.setView([latitude, longitude], 18);
            }
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @if (filled($googleMapsKey))
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=geometry&callback=initEmployeeTrackingMap&v=weekly" onerror="window.gm_authFailure()"></script>
    @endif
@endpush
