@extends('layouts.app')

@section('title', 'Timeline')
@section('content_class', 'pb-0')

@php($mapProvider = $mapSettings['map_provider'] ?? 'google')
@php($googleMapsKey = $mapProvider === 'google' ? ($mapSettings['google_maps_api_key'] ?? '') : '')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .timeline-map-wrapper {
            position: relative;
            width: 100%;
            height: calc(100vh - 165px);
            min-height: 660px;
            overflow: hidden;
            border-radius: 6px;
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
            top: 72px;
            left: 18px;
            width: min(390px, calc(100% - 36px));
            max-height: calc(100% - 96px);
            overflow: hidden;
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
            background: #fff;
            border: 1px solid #e6ebf2;
            border-radius: 6px;
        }

        .timeline-summary-card .card-body {
            padding: 12px 14px;
        }

        .timeline-summary-card dt,
        .timeline-summary-card dd {
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .timeline-summary-card dt {
            color: #667085;
            font-size: 12px;
            font-weight: 600;
            padding-right: 14px;
        }

        .timeline-summary-card .card-header {
            background: transparent;
            border-bottom-color: rgba(255, 255, 255, .65);
            color: #fff;
        }

        .timeline-summary-card .card-body {
            color: #1f2937;
            font-size: 12px;
        }

        .timeline-overlay-shell {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 6px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .14);
            padding: 10px;
            backdrop-filter: blur(6px);
        }

        .timeline-card-scroll {
            max-height: calc(100vh - 330px);
            overflow-y: auto;
            padding-right: 2px;
        }

        .timeline-partial-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 5px 8px;
        }

        .bg-soft-purple {
            background: #f3e8ff;
        }

        .text-purple {
            color: #7c3aed;
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

        .timeline-leaflet-pin.numbered-pin {
            width: 36px;
            height: 44px;
            border-radius: 18px 18px 18px 4px;
            transform: rotate(-45deg);
        }

        .timeline-leaflet-pin.numbered-pin span {
            transform: rotate(45deg);
            color: #fff;
            font-size: 13px;
            line-height: 1;
        }

        .timeline-leaflet-pin.numbered-pin::after {
            display: none;
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

        .timeline-leaflet-pin.low-signal-pin {
            background: #f59e0b;
        }

        .timeline-leaflet-pin.offline-pin {
            background: #7c3aed;
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
                max-height: 50%;
            }

            .timeline-card-scroll {
                max-height: 260px;
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
            defaultRouteMode: @json(request('route_mode', $mapSettings['default_route_mode'] ?? 'actual')),
            actualGpsRouteEnabled: @json($mapSettings['actual_gps_route_enabled'] ?? true),
            roadRouteEnabled: @json($mapSettings['road_route_enabled'] ?? true),
            distanceUnit: @json($mapSettings['distance_unit'] ?? 'km'),
            showOfflinePoints: @json($mapSettings['show_offline_points'] ?? true),
            showLowSignalPoints: @json($mapSettings['show_low_signal_points'] ?? true),
            showGaps: @json($mapSettings['show_gaps'] ?? true),
            lowSignalThreshold: Number(@json($mapSettings['low_signal_threshold'] ?? 2)),
        };

        let timelineMap;
        let timelineMapProvider = null;
        let timelineMarkers = [];
        let timelinePolylines = [];
        let timelineDirectionsRenderers = [];
        let timelineRenderToken = 0;
        let timelineLoadToken = 0;
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
            const loadToken = ++timelineLoadToken;
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
                if (loadToken !== timelineLoadToken) {
                    return;
                }
                document.getElementById('timelineOverlayCard').innerHTML = overlayShell('Unable to load timeline.');
                return;
            }

            const payload = await response.json();
            if (loadToken !== timelineLoadToken) {
                return;
            }
            if (timelineConfig.gpsDebug) {
                console.info('[EmployeeTracking] timeline response', payload);
            }

            renderTimeline(payload);
        }

        function renderTimeline(data) {
            const items = filterOpenAttendanceCheckOutItems(data.timeLineItems || [], data);
            lastTimelinePayload = data;
            clearTimelineMap();
            const renderToken = ++timelineRenderToken;

            let contents = '';
            let finalDistance = '- KM';
            let gpsDistance = data.gpsDistanceKm ?? data.totalKM ?? null;
            let directionsDistance = data.directionsDistanceKm ?? null;
            const addressLookups = [];
            let lastVisibleStop = null;
            const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);
            const routeMarkers = buildRouteMarkersFromSegments(movementPaths);
            const attendanceMarkers = buildAttendanceMarkers(items);

            if (items.length > 0) {
                items.forEach(function (item, index) {
                    const latitude = Number(item.latitude);
                    const longitude = Number(item.longitude);
                    const isMovementPoint = item.type === 'vehicle' || item.type === 'walk';
                    const isCollapsedStill = shouldCollapseStillStop(item, lastVisibleStop);

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

                    if (item.type === 'still' && !isCollapsedStill) {
                        lastVisibleStop = {...item, latitude, longitude};
                    }

                    if (isMovementPoint || isCollapsedStill) {
                        return;
                    } else {
                        contents += `
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <span><i class="ti ti-clock me-1"></i>${escapeHtml(item.startTime || '-')} - ${escapeHtml(item.endTime || '-')}</span>
                                    ${batteryHtml(item.batteryPercentage)}
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <h6 class="text-primary mb-1">${escapeHtml(item.type || 'Tracking')}</h6>
                                    <span class="small">${accuracyHtml(item.accuracy)}</span>
                                </div>
                                ${trackingBadges(item)}
                                <div class="small text-muted" id="${addressId}">${address}</div>
                            </div>
                        </div>
                        `;
                    }
                });

                contents = `${attendanceSessionCard(items, data)}${contents}`;

                drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken);

                if (gpsDistance !== undefined && gpsDistance !== null) {
                    finalDistance = `${Number(gpsDistance).toFixed(2)} KM`;
                } else if (movementPaths.length) {
                    let totalMeters = 0;
                    movementPaths.forEach((path) => {
                        for (let i = 0; i < path.length - 1; i++) {
                            totalMeters += computeDistanceMeters(path[i], path[i + 1]);
                        }
                    });
                    finalDistance = `${(totalMeters / 1000).toFixed(2)} KM`;
                } else {
                    finalDistance = '0.00 KM';
                }
            } else {
                contents = '<p class="text-muted mb-0">No data!</p>';
                drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken);
            }

            document.getElementById('timelineOverlayCard').innerHTML = overlayShell(contents, data, finalDistance, directionsDistance);
            updateDistanceDisplay(finalDistance, directionsDistance);
            resolveMissingAddresses(addressLookups);
        }

        function filterOpenAttendanceCheckOutItems(items, data = {}) {
            const openAttendanceIds = new Set();
            const collectAttendance = (attendance) => {
                if (!attendance) {
                    return;
                }

                const attendanceId = Number(attendance.id ?? attendance.attendance_id);
                if (Number.isFinite(attendanceId) && (attendance.is_open === true || !attendance.check_out_at)) {
                    openAttendanceIds.add(attendanceId);
                }
            };

            (data.attendances || []).forEach(collectAttendance);
            (data.attendanceSessions || []).forEach((session) => collectAttendance(session.attendance));

            if (!openAttendanceIds.size) {
                return items;
            }

            return items.filter((item) => {
                const attendanceId = Number(item.attendanceId ?? item.attendance_id);

                return item.type !== 'checkOut' || !openAttendanceIds.has(attendanceId);
            });
        }

        function attendanceSessionCard(items, data = {}) {
            const checkInIndex = items.findIndex((item) => item.type === 'checkIn');
            const checkOutIndex = items.findIndex((item) => item.type === 'checkOut');
            const checkInItem = checkInIndex >= 0 ? items[checkInIndex] : null;
            const checkOutItem = checkOutIndex >= 0 ? items[checkOutIndex] : null;
            const attendance = (data.attendanceSessions?.[0]?.attendance || data.attendances?.[0] || {});
            const firstTracking = items[0] || null;
            const lastTracking = items.length ? items[items.length - 1] : null;
            const isOpenAttendance = attendance.is_open === true || !attendance.check_out_at;

            if (!checkInItem && !checkOutItem && !attendance.id) {
                return '';
            }

            const startTime = checkInItem?.startTime || attendance.check_in_time || '-';
            const endTime = isOpenAttendance ? 'Not checked out' : (checkOutItem?.startTime || checkOutItem?.endTime || attendance.check_out_time || '-');
            const batteryValue = isOpenAttendance
                ? (checkInItem?.batteryPercentage ?? firstTracking?.batteryPercentage)
                : (checkOutItem?.batteryPercentage ?? lastTracking?.batteryPercentage ?? checkInItem?.batteryPercentage ?? firstTracking?.batteryPercentage);
            const checkInAccuracy = accuracyHtml(checkInItem?.accuracy ?? firstTracking?.accuracy);
            const checkOutAccuracy = isOpenAttendance ? '-' : accuracyHtml(checkOutItem?.accuracy);
            const checkInBattery = batteryHtml(checkInItem?.batteryPercentage ?? firstTracking?.batteryPercentage);
            const checkOutBattery = isOpenAttendance ? '' : batteryHtml(checkOutItem?.batteryPercentage);
            const statusLabel = attendance.status_label || (isOpenAttendance ? 'Active' : (attendance.status || 'Present'));

            const checkInAddress = checkInItem
                ? `<div class="session-address" id="timelineAddress${checkInIndex}">${itemAddressHtml(checkInItem)}</div>`
                : '<div class="session-address text-muted">Attendance check-in time saved. Check-in GPS marker not available.</div>';

            const checkOutAddress = isOpenAttendance
                ? '<div class="session-address text-muted">Employee is still checked in.</div>'
                : (checkOutItem
                ? `<div class="session-address" id="timelineAddress${checkOutIndex}">${itemAddressHtml(checkOutItem)}</div>`
                : '<div class="session-address text-muted">Attendance check-out time saved. Check-out GPS marker not available.</div>');

            return `
                <div class="card attendance-session-card mb-2 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <span><i class="ti ti-clock me-1"></i>${escapeHtml(startTime)} - ${escapeHtml(endTime)}</span>
                            ${batteryHtml(batteryValue)}
                        </div>
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <h6 class="session-heading mb-0"><span class="badge bg-primary me-1">1</span>Attendance</h6>
                            <span class="small">${escapeHtml(statusLabel)}</span>
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

        async function drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken) {
            const routePoints = movementPaths.flat();
            const singleVisiblePoint = routePoints[0] || firstTimelineCoordinate(items);
            const boundsPoints = routePoints.length ? routePoints : (singleVisiblePoint ? [singleVisiblePoint] : []);
            const routeMode = currentRouteMode(data);

            if (timelineConfig.gpsDebug) {
                console.info('[EmployeeTracking] route render plan', {
                    routeMode,
                    gpsSegments: movementPaths.length,
                    gpsVertices: movementPaths.reduce((count, segment) => count + segment.length, 0),
                    markerCount: routeMarkers.length,
                });
            }

            if (!boundsPoints.length) {
                resetTimelineMapView();
                return;
            }

            for (const routePath of movementPaths) {
                if (renderToken !== timelineRenderToken) {
                    return;
                }

                drawRoutePolyline(routePath);
                await wait(20);
            }

            if (routeMarkers.length) {
                routeMarkers.forEach(drawNumberedRouteMarker);
            } else if (singleVisiblePoint) {
                drawNumberedRouteMarker({...singleVisiblePoint, label: 1, isEnd: false, title: 'Timeline point'});
            }

            attendanceMarkers.forEach(drawAttendanceMarker);

            if (timelineConfig.gpsDebug) {
                drawGapIndicators(data?.trackingHealth?.largest_gaps || []);
            }

            fitTimelineMapToPoints(boundsPoints.concat(routeMarkers).concat(attendanceMarkers));
        }

        function currentRouteMode(data = lastTimelinePayload) {
            return 'actual';
        }

        function isLowConfidenceRoute(data = {}) {
            const health = data?.trackingHealth || {};
            const coverage = Number(health.tracking_coverage_percentage);
            const gapCount = Number(health.gap_count);

            return (Number.isFinite(coverage) && coverage < 60)
                || (Number.isFinite(gapCount) && gapCount > 0);
        }

        function routeModeLabel(data = lastTimelinePayload) {
            return isLowConfidenceRoute(data)
                ? 'Partial Actual GPS Route'
                : 'Actual GPS Route';
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
                    const path = Array.isArray(points)
                        ? filterPolylineCoordinates(points.map((point) => itemLatLng(point)).filter(Boolean), 3)
                        : [];

                    return path;
                })
                .filter((segment) => segment.length >= 2);
        }

        function buildRouteMarkersFromSegments(movementPaths) {
            const allRoutePoints = Array.isArray(movementPaths) ? movementPaths.flat() : [];
            const candidates = [];

            movementPaths.forEach((path, segmentIndex) => {
                if (!path.length) {
                    return;
                }

                if (segmentIndex === 0) {
                    candidates.push(path[0]);
                }

                if (path.length > 3) {
                    candidates.push(path[Math.floor(path.length / 2)]);
                }

                candidates.push(path[path.length - 1]);
            });

            let markerPoints = dedupeCloseRoutePoints(candidates, 50);
            if (markerPoints.length > 10) {
                markerPoints = sampleRoutePoints(markerPoints, 10);
            }

            if (markerPoints.length < 2 && allRoutePoints.length > 1) {
                markerPoints = [allRoutePoints[0], allRoutePoints[allRoutePoints.length - 1]];
            }

            return markerPoints.map((point, index) => ({
                ...point,
                label: index + 1,
                isEnd: index === markerPoints.length - 1 && markerPoints.length > 1,
                title: index === 0
                    ? 'Route start'
                    : (index === markerPoints.length - 1 ? 'Route end' : `Route point ${index + 1}`),
            }));
        }

        function buildAttendanceMarkers(items) {
            if (!Array.isArray(items)) {
                return [];
            }

            return items
                .filter((item) => item.type === 'checkIn' || item.type === 'checkOut')
                .map((item) => {
                    const point = itemLatLng(item);
                    if (!point) {
                        return null;
                    }

                    return {
                        ...point,
                        type: item.type,
                        label: item.type === 'checkIn' ? 'IN' : 'OUT',
                        title: item.description || (item.type === 'checkIn' ? 'Attendance check-in location' : 'Attendance check-out location'),
                    };
                })
                .filter(Boolean);
        }

        function sampleRoutePoints(points, maxMarkers) {
            if (points.length <= maxMarkers) {
                return points;
            }

            const sampled = [];
            const lastIndex = points.length - 1;
            for (let i = 0; i < maxMarkers; i++) {
                sampled.push(points[Math.round((i / (maxMarkers - 1)) * lastIndex)]);
            }

            return dedupeCloseRoutePoints(sampled, 20);
        }

        function dedupeCloseRoutePoints(points, minDistanceMeters = 25) {
            const deduped = [];
            points.forEach((point) => {
                if (!point || deduped.some((existing) => computeDistanceMeters(existing, point) < minDistanceMeters)) {
                    return;
                }
                deduped.push(point);
            });

            return deduped;
        }

        function itemLatLng(item) {
            const latitude = Number(item.latitude ?? item.lat);
            const longitude = Number(item.longitude ?? item.lng);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude === 0 || longitude === 0) {
                return null;
            }

            return {
                lat: latitude,
                lng: longitude,
                id: item.id ?? null,
                recorded_at: item.recorded_at ?? item.recordedAt ?? null,
                type: item.type ?? item.tracking_type ?? null,
            };
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
                    strokeColor: '#0d47ff',
                    strokeOpacity: .95,
                    strokeWeight: 4,
                    map: timelineMap,
                });
                timelinePolylines.push(polyline);
            } else if (timelineMapProvider === 'leaflet') {
                const coords = cleanPath.map((p) => [p.lat, p.lng]);
                const polyline = L.polyline(coords, {
                    color: '#0d47ff',
                    opacity: .95,
                    weight: 4,
                }).addTo(timelineMap);
                timelinePolylines.push(polyline);
            }
        }

        function drawNumberedRouteMarker(point) {
            const lat = Number(point?.lat);
            const lng = Number(point?.lng);
            const label = Number(point?.label || 1);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const color = point.isEnd ? '#ef4444' : '#16a34a';
            const title = point.title || (point.isEnd ? 'Route end' : `Route point ${label}`);
            if (timelineMapProvider === 'google') {
                const marker = new google.maps.Marker({
                    position: new google.maps.LatLng(lat, lng),
                    map: timelineMap,
                    icon: {
                        url: numberedPinSvg(label, color),
                        scaledSize: new google.maps.Size(38, 46),
                        anchor: new google.maps.Point(19, 44),
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
                    html: `<div class="timeline-leaflet-pin numbered-pin ${point.isEnd ? 'route-end-pin' : 'route-start-pin'}"><span>${escapeHtml(label)}</span></div>`,
                    iconSize: [36, 44],
                    iconAnchor: [18, 42],
                });
                const marker = L.marker([lat, lng], {icon, title}).addTo(timelineMap);
                marker.on('click', function () {
                    focusTimelinePoint(lat, lng);
                });
                timelineMarkers.push(marker);
            }
        }

        function drawAttendanceMarker(point) {
            const lat = Number(point?.lat);
            const lng = Number(point?.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const label = point.label || (point.type === 'checkOut' ? 'OUT' : 'IN');
            const color = point.type === 'checkOut' ? '#ef4444' : '#0d6efd';
            const title = point.title || (point.type === 'checkOut' ? 'Attendance check-out location' : 'Attendance check-in location');

            if (timelineMapProvider === 'google') {
                const marker = new google.maps.Marker({
                    position: new google.maps.LatLng(lat, lng),
                    map: timelineMap,
                    icon: {
                        url: labelledPinSvg(label, color),
                        scaledSize: new google.maps.Size(42, 48),
                        anchor: new google.maps.Point(21, 46),
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
                    html: `<div class="timeline-leaflet-pin attendance-pin"><span>${escapeHtml(label)}</span></div>`,
                    iconSize: [36, 44],
                    iconAnchor: [18, 42],
                });
                const marker = L.marker([lat, lng], {icon, title}).addTo(timelineMap);
                marker.on('click', function () {
                    focusTimelinePoint(lat, lng);
                });
                timelineMarkers.push(marker);
            }
        }

        function fitTimelineMapToPoints(points) {
            const cleanPoints = (points || [])
                .map((point) => itemLatLng(point))
                .filter(Boolean);

            if (!cleanPoints.length) {
                resetTimelineMapView();
                return;
            }

            if (cleanPoints.length === 1) {
                focusTimelinePoint(cleanPoints[0].lat, cleanPoints[0].lng);
                if (timelineMapProvider === 'google' && timelineMap?.setZoom) {
                    timelineMap.setZoom(Math.min(timelineMap.getZoom() || 15, 15));
                } else if (timelineMapProvider === 'leaflet' && timelineMap?.setZoom) {
                    timelineMap.setZoom(15);
                }
                return;
            }

            if (timelineMapProvider === 'google') {
                const bounds = new google.maps.LatLngBounds();
                cleanPoints.forEach((p) => bounds.extend(new google.maps.LatLng(p.lat, p.lng)));
                timelineMap.fitBounds(bounds, 72);
                const listener = google.maps.event.addListenerOnce(timelineMap, 'idle', function () {
                    if (timelineMap.getZoom() > 16) {
                        timelineMap.setZoom(16);
                    }
                    google.maps.event.removeListener(listener);
                });
            } else if (timelineMapProvider === 'leaflet') {
                const coords = cleanPoints.map((p) => [p.lat, p.lng]);
                timelineMap.fitBounds(L.latLngBounds(coords), {padding: [42, 42], maxZoom: 16});
            }
        }

        function resetTimelineMapView() {
            if (timelineMapProvider === 'google' && timelineMap?.setCenter) {
                timelineMap.setCenter({lat: timelineConfig.center.lat, lng: timelineConfig.center.lng});
                timelineMap.setZoom(timelineConfig.zoom);
            } else if (timelineMapProvider === 'leaflet' && timelineMap?.setView) {
                timelineMap.setView([timelineConfig.center.lat, timelineConfig.center.lng], timelineConfig.zoom);
            }
        }

        function firstTimelineCoordinate(items) {
            if (!Array.isArray(items)) {
                return null;
            }

            for (const item of items) {
                const point = itemLatLng(item);
                if (point) {
                    return point;
                }
            }

            return null;
        }

        function drawGapIndicators(gaps) {
            if (!timelineConfig.showGaps || !Array.isArray(gaps) || !gaps.length) {
                return;
            }

            gaps.forEach((gap) => {
                const previous = itemLatLng(gap.previous_coordinate || {});
                const current = itemLatLng(gap.current_coordinate || {});

                if (!previous || !current) {
                    return;
                }

                const title = `Missing tracking gap: ${gap.gap_minutes || '-'} minutes`;
                if (timelineMapProvider === 'google') {
                    const line = new google.maps.Polyline({
                        path: [
                            new google.maps.LatLng(previous.lat, previous.lng),
                            new google.maps.LatLng(current.lat, current.lng),
                        ],
                        geodesic: false,
                        strokeColor: '#f59e0b',
                        strokeOpacity: 0,
                        strokeWeight: 3,
                        icons: [{
                            icon: {path: 'M 0,-1 0,1', strokeColor: '#f59e0b', strokeOpacity: 1, scale: 3},
                            offset: '0',
                            repeat: '14px',
                        }],
                        map: timelineMap,
                    });
                    line.addListener('click', function () {
                        focusTimelinePoint(current.lat, current.lng);
                    });
                    timelinePolylines.push(line);
                    return;
                }

                if (timelineMapProvider === 'leaflet') {
                    const line = L.polyline([[previous.lat, previous.lng], [current.lat, current.lng]], {
                        color: '#f59e0b',
                        weight: 3,
                        dashArray: '8 8',
                    }).addTo(timelineMap);
                    line.bindTooltip(title);
                    timelinePolylines.push(line);
                }
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
            return `
                <div class="timeline-overlay-shell">
                    <div class="timeline-summary-card mb-2">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <strong>${escapeHtml(routeModeLabel(data))}</strong>
                                ${partialRouteBadge(data)}
                            </div>
                            <dl class="row mb-0">
                                <dt class="col-6">Employee</dt>
                                <dd class="col-6">${escapeHtml(data.employeeName || '-')}</dd>
                                <dt class="col-6">Total tracked time</dt>
                                <dd class="col-6">${escapeHtml(data.totalTrackedTime || '00:00:00')}</dd>
                                <dt class="col-6">Total attendance time</dt>
                                <dd class="col-6">${escapeHtml(data.totalAttendanceTime || '00:00:00')}</dd>
                                <dt class="col-6">Total travelled distance</dt>
                                <dd class="col-6" id="timelineGpsDistance">${escapeHtml(gpsDistance)}</dd>
                                <dt class="col-6">Device information</dt>
                                <dd class="col-6">${escapeHtml(data.deviceInfo || '-')}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="timeline timeline-card-scroll mt-1">${contents}</div>
                </div>
            `;
        }

        function partialRouteBadge(data = {}) {
            return isLowConfidenceRoute(data)
                ? '<span class="timeline-partial-badge" title="Detailed route coverage diagnostics are available in Debug Report.">Partial route</span>'
                : '';
        }

        function formatDateTimeTime(value) {
            if (!value) {
                return '-';
            }

            const parts = String(value).split(' ');
            return parts.length > 1 ? parts[1] : value;
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

        function trackingBadges(item) {
            const badges = [];
            if (item.isOffline && timelineConfig.showOfflinePoints) {
                badges.push('<span class="badge bg-soft-purple text-purple me-1">Offline synced</span>');
            }
            if (isLowSignal(item) && timelineConfig.showLowSignalPoints) {
                badges.push(`<span class="badge bg-soft-warning text-warning me-1">Low signal ${escapeHtml(item.signalStrength)}</span>`);
            }
            if (!item.isGPSOn) {
                badges.push('<span class="badge bg-soft-danger text-danger me-1">GPS off</span>');
            }

            return badges.length ? `<div class="mb-2">${badges.join('')}</div>` : '';
        }

        function isLowSignal(item) {
            if (!timelineConfig.showLowSignalPoints) {
                return false;
            }
            const signal = signalNumber(item.signalStrength);
            return signal !== null && signal <= timelineConfig.lowSignalThreshold;
        }

        function signalNumber(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }
            if (Number.isFinite(Number(value))) {
                return Number(value);
            }
            const match = String(value).match(/-?\d+/);
            return match ? Number(match[0]) : null;
        }

        function numberedPinSvg(number, color) {
            const label = String(number || '');
            const safeColor = /^#[0-9a-f]{6}$/i.test(color) ? color : '#ef1d0d';
            return labelledPinSvg(label, safeColor);
        }

        function labelledPinSvg(label, color) {
            const safeColor = /^#[0-9a-f]{6}$/i.test(color) ? color : '#ef1d0d';
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="38" height="46" viewBox="0 0 38 46">
                    <path d="M19 45C15.6 39.5 5 27.8 5 18.6C5 10.4 11.3 4 19 4C26.7 4 33 10.4 33 18.6C33 27.8 22.4 39.5 19 45Z" fill="${safeColor}" stroke="#fff" stroke-width="3"/>
                    <circle cx="19" cy="18" r="11.5" fill="rgba(255,255,255,.14)"/>
                    <text x="19" y="23" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#fff">${escapeHtml(label)}</text>
                </svg>
            `;

            return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
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
                routeModeNode.textContent = routeModeLabel(lastTimelinePayload);
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
