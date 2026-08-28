@extends('layouts.app')

@section('title', 'Timeline')
@section('content_class', 'pb-0')

@php($mapProvider = $mapSettings['map_provider'] ?? 'google')
@php($googleMapsKey = $mapProvider === 'google' ? ($mapSettings['google_maps_api_key'] ?? '') : '')
@php($isLeafletView = blank($googleMapsKey))

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

        .timeline-map-provider-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 4;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(226, 232, 240, .95);
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 7px 10px;
            pointer-events: none;
        }

        .timeline-map-provider-badge i {
            color: #0f766e;
            font-size: 14px;
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

    <div class="timeline-map-wrapper shadow-sm">
        <div id="employeeTrackingMap"></div>
        @if ($isLeafletView)
            <div class="timeline-map-provider-badge">
                <i class="ti ti-map-2"></i>
                <span>OpenStreetMap view</span>
            </div>
        @endif
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
            reverseGeocodeUrl: @json(route('dashboard.reverseGeocode')),
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
        let timelineActualRoutePolylineCount = 0;
        let timelineDirectionsRenderers = [];
        let timelineRenderToken = 0;
        let timelineLoadToken = 0;
        let lastTimelinePayload = null;
        let timelineHasFittedMapForSession = false;
        let timelineRequestInProgress = false;
        let timelineMapInitializing = false;
        let timelineGoogleRoutingDisabled = false;
        const routeCache = new Map();

        function getRouteCacheKey(chunk, travelMode) {
            if (!chunk || !chunk.length) return '';
            const userId = document.getElementById('trackingEmployee')?.value || '';
            const date = document.getElementById('trackingDate')?.value || '';
            const start = chunk[0];
            const end = chunk[chunk.length - 1];
            const startKey = `${Number(start.lat).toFixed(5)},${Number(start.lng).toFixed(5)}`;
            const endKey = `${Number(end.lat).toFixed(5)},${Number(end.lng).toFixed(5)}`;
            return `${userId}:${date}:${travelMode}:${chunk.length}:${startKey}:${endKey}`;
        }

        function initEmployeeTrackingMap() {
            if (timelineMap || timelineMapInitializing) {
                return;
            }
            timelineMapInitializing = true;

            try {
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
            } finally {
                timelineMapInitializing = false;
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

        let timelineAutoRefreshTimer = null;
        let isTimelineLoading = false;

        function stopTimelineAutoRefresh() {
            if (timelineAutoRefreshTimer) {
                clearInterval(timelineAutoRefreshTimer);
                timelineAutoRefreshTimer = null;
            }
        }

        function startTimelineAutoRefreshIfNeeded(data, date) {
            stopTimelineAutoRefresh();

            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const todayString = `${year}-${month}-${day}`;

            if (date !== todayString) {
                return;
            }

            const hasActiveAttendance = (data?.attendances || []).some((att) => att.is_open === true || !att.check_out_at)
                || (data?.attendanceSessions || []).some((sess) => sess?.attendance?.is_open === true || !sess?.attendance?.check_out_at);

            if (hasActiveAttendance) {
                timelineAutoRefreshTimer = setInterval(loadTimelineData, 15000);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('trackingEmployee')?.addEventListener('change', function () {
                stopTimelineAutoRefresh();
                timelineHasFittedMapForSession = false;
                routeCache.clear();
                loadTimelineData(true);
            });
            document.getElementById('trackingDate')?.addEventListener('change', function () {
                stopTimelineAutoRefresh();
                timelineHasFittedMapForSession = false;
                routeCache.clear();
                loadTimelineData(true);
            });

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

        async function loadTimelineData(isUserAction = false) {
            if (isTimelineLoading || timelineRequestInProgress) {
                return;
            }

            timelineRequestInProgress = true;
            isTimelineLoading = true;
            const loadToken = ++timelineLoadToken;

            if (!timelineMap) {
                initEmployeeTrackingMap();
            }

            const userId = document.getElementById('trackingEmployee')?.value;
            const date = document.getElementById('trackingDate')?.value;

            if (!userId || !date) {
                stopTimelineAutoRefresh();
                timelineRequestInProgress = false;
                isTimelineLoading = false;
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

            try {
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
                    stopTimelineAutoRefresh();
                    clearTimelineMap();
                    resetTimelineMapView();
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

                await renderTimeline(payload, isUserAction);
                startTimelineAutoRefreshIfNeeded(payload, date);
            } catch (e) {
                if (loadToken === timelineLoadToken) {
                    stopTimelineAutoRefresh();
                }
            } finally {
                isTimelineLoading = false;
                timelineRequestInProgress = false;
            }
        }

        async function renderTimeline(data, isUserAction = false) {
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
            const attendanceMarkers = buildAttendanceMarkers(items);
            const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);
            const routeMarkers = buildRouteMarkersFromSegments(movementPaths, attendanceMarkers);

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
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <h6 class="text-primary mb-0">${itemActivityBadgeHtml(item)} ${escapeHtml(item.type || 'Tracking')}</h6>
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

                await drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken, isUserAction);

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
                await drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken);
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

        async function drawTimelineRoute(data, movementPaths, routeMarkers, attendanceMarkers, items, renderToken, isUserAction = false) {
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

            const snappedSegments = [];
            for (const routePath of movementPaths) {
                if (renderToken !== timelineRenderToken) {
                    return;
                }

                const snappedPoints = await drawRoadFollowingPolyline(routePath, renderToken);
                snappedSegments.push(snappedPoints || routePath);
                await wait(20);
            }

            const mappedRouteMarkers = mapMarkersToSnappedRoute(routeMarkers, snappedSegments, movementPaths);
            const mappedAttendanceMarkers = mapAttendanceMarkersToSnappedRoute(attendanceMarkers, snappedSegments, movementPaths);

            if (mappedRouteMarkers.length) {
                mappedRouteMarkers.forEach(drawNumberedRouteMarker);
            } else if (singleVisiblePoint) {
                drawNumberedRouteMarker({...singleVisiblePoint, label: 1, isEnd: false, title: 'Timeline point'});
            }

            mappedAttendanceMarkers.forEach(drawAttendanceMarker);

            if (timelineConfig.gpsDebug) {
                drawGapIndicators(data?.trackingHealth?.largest_gaps || []);
            }

            if (isUserAction || !timelineHasFittedMapForSession) {
                fitTimelineMapToPoints(boundsPoints.concat(mappedRouteMarkers).concat(mappedAttendanceMarkers));
                timelineHasFittedMapForSession = true;
            }
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
            // Preserve the backend's authoritative segment endpoints. Interior
            // jitter may be omitted for display, but never the route endpoint.
            const filtered = [latLngs[0]];
            for (let i = 1; i < latLngs.length; i++) {
                const prev = filtered[filtered.length - 1];
                const curr = latLngs[i];
                if (i === latLngs.length - 1 || computeDistanceMeters(prev, curr) >= minDistanceMeters) {
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
                    if (segment?.attendance_id !== undefined) {
                        path.attendanceId = segment.attendance_id;
                    }
                    if (segment?.device_id !== undefined) {
                        path.deviceId = segment.device_id;
                    }
                    if (segment?.start_type !== undefined) {
                        path.startType = segment.start_type;
                    }

                    return path;
                })
                .filter((path) => path.length >= 2);
        }

        function buildRouteMarkersFromSegments(movementPaths, attendanceMarkers = []) {
            const allRoutePoints = Array.isArray(movementPaths) ? movementPaths.flat() : [];
            const candidates = [];
            const checkInMarker = attendanceMarkers.find((marker) => marker.type === 'checkIn') || null;
            const checkOutMarker = attendanceMarkers.find((marker) => marker.type === 'checkOut') || null;

            movementPaths.forEach((path, segmentIndex) => {
                if (!path.length) {
                    return;
                }

                if (segmentIndex === 0) {
                    candidates.push({ ...path[0], segmentIndex, isSegmentStart: true });
                }

                if (path.length > 3) {
                    candidates.push({ ...path[Math.floor(path.length / 2)], segmentIndex });
                }

                candidates.push({ ...path[path.length - 1], segmentIndex, isSegmentEnd: true });
            });

            let markerPoints = dedupeCloseRoutePoints(candidates, 50);
            if (markerPoints.length > 10) {
                markerPoints = sampleRoutePoints(markerPoints, 10);
            }

            if (markerPoints.length < 2 && allRoutePoints.length > 1) {
                markerPoints = [
                    { ...allRoutePoints[0], segmentIndex: 0, isSegmentStart: true },
                    { ...allRoutePoints[allRoutePoints.length - 1], segmentIndex: Math.max(0, movementPaths.length - 1), isSegmentEnd: true }
                ];
            }

            return markerPoints.map((point, index) => {
                const rawLat = Number(point.lat);
                const rawLng = Number(point.lng);
                return {
                    ...point,
                    rawLat,
                    rawLng,
                    label: index + 1,
                    isEnd: index === markerPoints.length - 1 && markerPoints.length > 1,
                    title: index === 0
                        ? 'Route start'
                        : (index === markerPoints.length - 1 ? 'Route end' : `Route point ${index + 1}`),
                };
            });
        }

        function findNearestSnappedPoint(rawPoint, snappedPoints, minSearchIndex = 0) {
            if (!rawPoint || !Array.isArray(snappedPoints) || snappedPoints.length === 0) {
                return { point: rawPoint, index: 0, distance: 0 };
            }

            const rawLat = Number(typeof rawPoint.lat === 'function' ? rawPoint.lat() : rawPoint.lat);
            const rawLng = Number(typeof rawPoint.lng === 'function' ? rawPoint.lng() : rawPoint.lng);

            if (!Number.isFinite(rawLat) || !Number.isFinite(rawLng)) {
                return { point: rawPoint, index: 0, distance: 0 };
            }

            if (snappedPoints.length === 1) {
                const pt = snappedPoints[0];
                const point = {
                    lat: Number(typeof pt.lat === 'function' ? pt.lat() : pt.lat),
                    lng: Number(typeof pt.lng === 'function' ? pt.lng() : pt.lng),
                };

                return {
                    point,
                    index: 0,
                    distance: computeDistanceMeters(
                        { lat: rawLat, lng: rawLng },
                        point
                    ),
                };
            }

            const start = Math.max(
                0,
                Math.min(minSearchIndex, snappedPoints.length - 2)
            );

            let bestPoint = null;
            let bestIndex = start;
            let minDistance = Infinity;

            // Find the closest POINT ON each route segment,
            // instead of only checking route vertices.
            for (let i = start; i < snappedPoints.length - 1; i++) {
                const a = snappedPoints[i];
                const b = snappedPoints[i + 1];

                const aLat = Number(typeof a.lat === 'function' ? a.lat() : a.lat);
                const aLng = Number(typeof a.lng === 'function' ? a.lng() : a.lng);
                const bLat = Number(typeof b.lat === 'function' ? b.lat() : b.lat);
                const bLng = Number(typeof b.lng === 'function' ? b.lng() : b.lng);

                if (
                    !Number.isFinite(aLat) ||
                    !Number.isFinite(aLng) ||
                    !Number.isFinite(bLat) ||
                    !Number.isFinite(bLng)
                ) {
                    continue;
                }

                // Local equirectangular projection.
                const refLat = ((aLat + bLat + rawLat) / 3) * Math.PI / 180;
                const cosLat = Math.cos(refLat);

                const ax = aLng * cosLat;
                const ay = aLat;
                const bx = bLng * cosLat;
                const by = bLat;
                const px = rawLng * cosLat;
                const py = rawLat;

                const dx = bx - ax;
                const dy = by - ay;
                const segmentLengthSquared = (dx * dx) + (dy * dy);

                let t = 0;

                if (segmentLengthSquared > 0) {
                    t = ((px - ax) * dx + (py - ay) * dy) / segmentLengthSquared;
                    t = Math.max(0, Math.min(1, t));
                }

                const projectedLat = aLat + ((bLat - aLat) * t);
                const projectedLng = aLng + ((bLng - aLng) * t);

                const projectedPoint = {
                    lat: projectedLat,
                    lng: projectedLng,
                };

                const distance = computeDistanceMeters(
                    { lat: rawLat, lng: rawLng },
                    projectedPoint
                );

                if (distance < minDistance) {
                    minDistance = distance;
                    bestPoint = projectedPoint;

                    // Keep the segment index so the next marker
                    // continues searching forward from here.
                    bestIndex = i;
                }
            }

            if (!bestPoint) {
                const fallback = snappedPoints[start];

                bestPoint = {
                    lat: Number(typeof fallback.lat === 'function' ? fallback.lat() : fallback.lat),
                    lng: Number(typeof fallback.lng === 'function' ? fallback.lng() : fallback.lng),
                };

                minDistance = computeDistanceMeters(
                    { lat: rawLat, lng: rawLng },
                    bestPoint
                );
            }

            return {
                point: bestPoint,
                index: bestIndex,
                distance: minDistance,
            };
        }

        function mapMarkersToSnappedRoute(routeMarkers, snappedSegments, movementPaths = []) {
            if (!Array.isArray(routeMarkers) || !routeMarkers.length) {
                return [];
            }
            if (!Array.isArray(snappedSegments) || !snappedSegments.length) {
                return routeMarkers;
            }

            const mappedMarkers = [];
            const markersBySegment = new Map();

            routeMarkers.forEach((marker) => {
                let segIdx = marker.segmentIndex;
                if (segIdx === undefined || segIdx < 0 || segIdx >= snappedSegments.length) {
                    segIdx = 0;
                }
                if (!markersBySegment.has(segIdx)) {
                    markersBySegment.set(segIdx, []);
                }
                markersBySegment.get(segIdx).push(marker);
            });

            markersBySegment.forEach((segmentMarkers, segIdx) => {
                const snapped = snappedSegments[segIdx];
                if (!Array.isArray(snapped) || snapped.length < 2) {
                    segmentMarkers.forEach((m) => mappedMarkers.push(m));
                    return;
                }

                let lastSnappedIndex = 0;
                segmentMarkers.forEach((marker, markerIdxInSeg) => {
                    const rawPoint = {
                        lat: Number(marker.rawLat ?? marker.lat),
                        lng: Number(marker.rawLng ?? marker.lng),
                    };
                    let snappedTarget;
                    let matchDist = 0;

                    if (marker.isSegmentStart || (segIdx === 0 && markerIdxInSeg === 0 && marker.label === 1)) {
                        const firstPt = snapped[0];
                        const lat = Number(typeof firstPt.lat === 'function' ? firstPt.lat() : firstPt.lat);
                        const lng = Number(typeof firstPt.lng === 'function' ? firstPt.lng() : firstPt.lng);
                        snappedTarget = { lat, lng };
                        matchDist = computeDistanceMeters(rawPoint, snappedTarget);
                        lastSnappedIndex = 0;
                    } else if (marker.isSegmentEnd && markerIdxInSeg === segmentMarkers.length - 1) {
                        const lastPt = snapped[snapped.length - 1];
                        const lat = Number(typeof lastPt.lat === 'function' ? lastPt.lat() : lastPt.lat);
                        const lng = Number(typeof lastPt.lng === 'function' ? lastPt.lng() : lastPt.lng);
                        snappedTarget = { lat, lng };
                        matchDist = computeDistanceMeters(rawPoint, snappedTarget);
                        lastSnappedIndex = snapped.length - 1;
                    } else {
                        const nearest = findNearestSnappedPoint(rawPoint, snapped, lastSnappedIndex);
                        snappedTarget = nearest.point;
                        matchDist = nearest.distance;
                        lastSnappedIndex = nearest.index;
                    }

                    if (timelineConfig.gpsDebug) {
                        console.info('[EmployeeTracking] Marker mapped to snapped route', {
                            markerLabel: marker.label || marker.type,
                            markerIndex: marker.label,
                            segmentIndex: segIdx,
                            rawPoint,
                            snappedPoint: snappedTarget,
                            distanceMeters: Math.round(matchDist * 100) / 100,
                        });
                    }

                    mappedMarkers.push({
                        ...marker,
                        rawLat: rawPoint.lat,
                        rawLng: rawPoint.lng,
                        lat: snappedTarget.lat,
                        lng: snappedTarget.lng,
                        snapped: true,
                        snapDistanceMeters: matchDist,
                    });
                });
            });

            return mappedMarkers.sort((a, b) => (Number(a.label) || 0) - (Number(b.label) || 0));
        }

        function mapAttendanceMarkersToSnappedRoute(attendanceMarkers, snappedSegments, movementPaths = []) {
            if (!Array.isArray(attendanceMarkers) || !attendanceMarkers.length) {
                return [];
            }
            if (!Array.isArray(snappedSegments) || !snappedSegments.length) {
                return attendanceMarkers;
            }

            return attendanceMarkers.map((marker) => {
                const rawPoint = {
                    lat: Number(marker.rawLat ?? marker.lat),
                    lng: Number(marker.rawLng ?? marker.lng),
                };

                if (marker.type === 'checkIn' && snappedSegments.length > 0 && snappedSegments[0].length >= 2) {
                    const firstSnapped = snappedSegments[0][0];
                    const firstLat = Number(typeof firstSnapped.lat === 'function' ? firstSnapped.lat() : firstSnapped.lat);
                    const firstLng = Number(typeof firstSnapped.lng === 'function' ? firstSnapped.lng() : firstSnapped.lng);
                    const dist = computeDistanceMeters(rawPoint, { lat: firstLat, lng: firstLng });
                    if (dist <= 250) {
                        if (timelineConfig.gpsDebug) {
                            console.info('[EmployeeTracking] Attendance checkIn marker mapped to snapped route start', {
                                rawPoint,
                                snappedPoint: { lat: firstLat, lng: firstLng },
                                distanceMeters: Math.round(dist * 100) / 100,
                            });
                        }
                        return {
                            ...marker,
                            rawLat: rawPoint.lat,
                            rawLng: rawPoint.lng,
                            lat: firstLat,
                            lng: firstLng,
                            snapped: true,
                            snapDistanceMeters: dist,
                        };
                    }
                }

                if (marker.type === 'checkOut' && snappedSegments.length > 0) {
                    const lastSeg = snappedSegments[snappedSegments.length - 1];
                    if (lastSeg && lastSeg.length >= 2) {
                        const lastSnapped = lastSeg[lastSeg.length - 1];
                        const lastLat = Number(typeof lastSnapped.lat === 'function' ? lastSnapped.lat() : lastSnapped.lat);
                        const lastLng = Number(typeof lastSnapped.lng === 'function' ? lastSnapped.lng() : lastSnapped.lng);
                        const dist = computeDistanceMeters(rawPoint, { lat: lastLat, lng: lastLng });
                        if (dist <= 250) {
                            if (timelineConfig.gpsDebug) {
                                console.info('[EmployeeTracking] Attendance checkOut marker mapped to snapped route end', {
                                    rawPoint,
                                    snappedPoint: { lat: lastLat, lng: lastLng },
                                    distanceMeters: Math.round(dist * 100) / 100,
                                });
                            }
                            return {
                                ...marker,
                                rawLat: rawPoint.lat,
                                rawLng: rawPoint.lng,
                                lat: lastLat,
                                lng: lastLng,
                                snapped: true,
                                snapDistanceMeters: dist,
                            };
                        }
                    }
                }

                return {
                    ...marker,
                    rawLat: rawPoint.lat,
                    rawLng: rawPoint.lng,
                };
            });
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

                    const rawLat = Number(point.lat);
                    const rawLng = Number(point.lng);

                    return {
                        ...point,
                        rawLat,
                        rawLng,
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

        function isWalkingPath(path) {
            if (!path) return false;
            if (path.startType === 'walk' || path.startType === 'walking') return true;
            const first = Array.isArray(path) ? path[0] : null;
            if (first?.type === 'walk' || first?.type === 'walking') return true;
            const activity = String(first?.activity || '').toLowerCase();
            return activity.includes('walk');
        }

        function drawRoutePolyline(latLngs, renderToken) {
            const cleanPath = filterPolylineCoordinates(latLngs, 3);

            if (cleanPath.length < 2) {
                return;
            }

            const color = isWalkingPath(latLngs) ? '#f5c400' : '#0d47ff';

            if (timelineMapProvider === 'google') {
                const gPath = cleanPath.map((p) => new google.maps.LatLng(p.lat, p.lng));
                const polyline = new google.maps.Polyline({
                    path: gPath,
                    geodesic: true,
                    strokeColor: '#0d47ff',
                    strokeOpacity: .95,
                    strokeWeight: 4,
                    zIndex: 10,
                    map: timelineMap,
                });
                if (color !== '#0d47ff') {
                    polyline.setOptions({ strokeColor: color });
                }
                timelinePolylines.push(polyline);
                timelineActualRoutePolylineCount++;
                if (timelineConfig.gpsDebug) {
                    console.info('[EmployeeTracking] actual GPS polyline drawn', {
                        routePolylineCount: timelineActualRoutePolylineCount,
                        routeRendering: 'gps_fallback',
                        pointCount: cleanPath.length,
                        endpoint: cleanPath[cleanPath.length - 1],
                    });
                }
            } else if (timelineMapProvider === 'leaflet') {
                const coords = cleanPath.map((p) => [p.lat, p.lng]);
                const polyline = L.polyline(coords, {
                    color,
                    opacity: .95,
                    weight: 4,
                    smoothFactor: 0,
                    noClip: true,
                }).addTo(timelineMap);
                timelinePolylines.push(polyline);
                timelineActualRoutePolylineCount++;
                if (timelineConfig.gpsDebug) {
                    console.info('[EmployeeTracking] actual GPS polyline drawn', {
                        routePolylineCount: timelineActualRoutePolylineCount,
                        routeRendering: 'gps_fallback',
                        pointCount: cleanPath.length,
                        endpoint: cleanPath[cleanPath.length - 1],
                    });
                }
            }
        }

        function downsamplePolylineCoordinates(latLngs, minDistanceMeters) {
            if (!Array.isArray(latLngs) || latLngs.length === 0) {
                return [];
            }

            const sampled = [latLngs[0]];
            for (let i = 1; i < latLngs.length; i++) {
                const prev = sampled[sampled.length - 1];
                if (computeDistanceMeters(prev, latLngs[i]) >= minDistanceMeters) {
                    sampled.push(latLngs[i]);
                }
            }

            const last = latLngs[latLngs.length - 1];
            if (sampled[sampled.length - 1] !== last) {
                sampled.push(last);
            }

            return sampled;
        }

        function chunkForDirectionsRequest(latLngs, maxPerRequest) {
            const maxPoints = Math.max(2, maxPerRequest || 10);
            const chunks = [];

            if (latLngs.length <= maxPoints) {
                chunks.push(latLngs.slice());
                return chunks;
            }

            for (let i = 0; i < latLngs.length; i += maxPoints - 1) {
                const end = Math.min(i + maxPoints, latLngs.length);
                chunks.push(latLngs.slice(i, end));
            }

            return chunks;
        }

        function prepareRoutingWaypoints(chunk) {
            if (!Array.isArray(chunk) || chunk.length <= 2) {
                return [];
            }
            const rawIntermediates = chunk.slice(1, -1);
            const filtered = [];

            for (let i = 0; i < rawIntermediates.length; i++) {
                const pt = rawIntermediates[i];
                if (!pt || !Number.isFinite(pt.lat) || !Number.isFinite(pt.lng)) continue;
                if (pt.accuracy !== undefined && Number(pt.accuracy) > 50) continue;

                if (filtered.length > 0) {
                    const prev = filtered[filtered.length - 1];
                    const dist = computeDistanceMeters(prev, pt);
                    if (dist < 15) {
                        continue;
                    }
                    if (pt.timestamp && prev.timestamp) {
                        const dtSec = Math.abs((new Date(pt.timestamp).getTime() - new Date(prev.timestamp).getTime()) / 1000);
                        if (dtSec > 0 && (dist / dtSec) > 42) {
                            continue;
                        }
                    }
                }
                filtered.push(pt);
            }

            if (filtered.length > 23) {
                const sampled = [];
                const lastIdx = filtered.length - 1;
                for (let k = 0; k < 23; k++) {
                    const idx = Math.round((k / 22) * lastIdx);
                    if (filtered[idx]) sampled.push(filtered[idx]);
                }
                return sampled;
            }

            return filtered;
        }

        function googleRouteFailureCode(error) {
            const message = String(error?.message || error?.code || error || '').toUpperCase();

            if (message.includes('REQUEST_DENIED')) return 'REQUEST_DENIED';
            if (message.includes('PERMISSION_DENIED')) return 'PERMISSION_DENIED';
            if (message.includes('SERVICE_DISABLED')) return 'SERVICE_DISABLED';
            if (message.includes('ZERO_RESULTS')) return 'ZERO_RESULTS';
            if (message.includes('OVER_QUERY_LIMIT') || message.includes('RESOURCE_EXHAUSTED')) return 'OVER_QUERY_LIMIT';
            if (message.includes('INVALID_REQUEST')) return 'INVALID_REQUEST';

            return 'ROUTING_UNAVAILABLE';
        }

        function isPermanentRoutingFailure(reason) {
            const r = String(reason || '').toUpperCase();
            return r.includes('REQUEST_DENIED') ||
                   r.includes('PERMISSION_DENIED') ||
                   r.includes('SERVICE_DISABLED');
        }

        function rawRouteFallback(chunk, reason) {
            const points = filterPolylineCoordinates(chunk, 0);

            if (timelineConfig.gpsDebug) {
                console.warn('[EmployeeTracking] road routing fallback', {
                    reason,
                    pointCount: points.length,
                });
            }

            return {points, routed: false, reason};
        }

        async function requestDrivingPath(chunk, isWalking = false) {
            if (!chunk || chunk.length < 2) {
                return rawRouteFallback([], 'INSUFFICIENT_POINTS');
            }

            if (timelineGoogleRoutingDisabled) {
                return { points: filterPolylineCoordinates(chunk, 0), routed: false, reason: 'API_DISABLED_PERMANENT' };
            }

            const travelMode = isWalking ? 'WALK' : 'DRIVE';
            const cacheKey = getRouteCacheKey(chunk, travelMode);
            if (routeCache.has(cacheKey)) {
                return routeCache.get(cacheKey);
            }

            try {
                const formattedPoints = chunk.map((pt) => ({
                    lat: Number(typeof pt.lat === 'function' ? pt.lat() : pt.lat),
                    lng: Number(typeof pt.lng === 'function' ? pt.lng() : pt.lng),
                })).filter((pt) => Number.isFinite(pt.lat) && Number.isFinite(pt.lng));

                if (formattedPoints.length < 2) {
                    return rawRouteFallback(chunk, 'INSUFFICIENT_POINTS');
                }

                const response = await fetch(timelineConfig.snapRouteUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': timelineConfig.csrfToken,
                    },
                    body: JSON.stringify({
                        _token: timelineConfig.csrfToken,
                        points: formattedPoints,
                    }),
                });

                if (!response.ok) {
                    const fallback = rawRouteFallback(chunk, `HTTP_${response.status}`);
                    return fallback;
                }

                const data = await response.json();
                if (data && data.snapped && Array.isArray(data.points) && data.points.length >= 2) {
                    const result = { points: data.points, routed: true, reason: null };
                    if (routeCache.size > 300) {
                        const firstKey = routeCache.keys().next().value;
                        if (firstKey) routeCache.delete(firstKey);
                    }
                    routeCache.set(cacheKey, result);
                    return result;
                }

                const fallback = rawRouteFallback(chunk, 'SNAP_FAILED');
                return fallback;
            } catch (err) {
                if (timelineConfig.gpsDebug) {
                    console.warn('[EmployeeTracking] Roads API snap request failed:', err);
                }
                const fallback = rawRouteFallback(chunk, 'NETWORK_ERROR');
                return fallback;
            }
        }

        async function drawRoadFollowingPolyline(routePath, renderToken) {
            if (!renderToken || renderToken !== timelineRenderToken) {
                return routePath;
            }

            if (timelineMapProvider !== 'google' || !window.google?.maps || !timelineConfig.roadRouteEnabled) {
                drawRoutePolyline(routePath);

                return routePath;
            }

            const isWalking = isWalkingPath(routePath);
            const chunks = chunkForDirectionsRequest(routePath, 300);
            const combinedPoints = [];
            const routingFailures = [];

            for (let chunkIndex = 0; chunkIndex < chunks.length; chunkIndex++) {
                const chunk = chunks[chunkIndex];
                if (renderToken !== timelineRenderToken) {
                    return routePath;
                }
                const result = await requestDrivingPath(chunk, isWalking);
                if (!result.routed) {
                    routingFailures.push(result.reason);
                }

                // Consecutive request chunks deliberately share an endpoint.
                // Omit only that duplicate, preserving first/last GPS points
                // and a continuous path through the waypoint limit boundary.
                const segmentPoints = result.points || [];
                if (combinedPoints.length && segmentPoints.length
                    && computeDistanceMeters(combinedPoints[combinedPoints.length - 1], segmentPoints[0]) < 1) {
                    combinedPoints.push(...segmentPoints.slice(1));
                } else {
                    combinedPoints.push(...segmentPoints);
                }
            }

            if (renderToken !== timelineRenderToken || combinedPoints.length < 2) {
                return routePath;
            }

            const routeColor = isWalking ? '#f5c400' : '#0d47ff';

            const polyline = new google.maps.Polyline({
                path: combinedPoints,
                geodesic: true,
                strokeColor: '#0d47ff',
                strokeOpacity: .95,
                strokeWeight: 4,
                zIndex: 10,
                map: timelineMap,
            });
            if (routeColor !== '#0d47ff') {
                polyline.setOptions({ strokeColor: routeColor });
            }
            timelinePolylines.push(polyline);
            timelineActualRoutePolylineCount++;
            if (timelineConfig.gpsDebug) {
                console.info('[EmployeeTracking] historical Directions route drawn', {
                    routePolylineCount: timelineActualRoutePolylineCount,
                    routeMode: 'DRIVING',
                    optimizeWaypoints: false,
                    waypointCount: combinedPoints.length,
                    batchCount: chunks.length,
                    routingFailures,
                    endpoint: combinedPoints[combinedPoints.length - 1],
                });
            }

            return combinedPoints;
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
                bindMarkerPopup(marker, point);
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
                bindMarkerPopup(marker, point);
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
                bindMarkerPopup(marker, point);
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
                bindMarkerPopup(marker, point);
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
            const center = {
                lat: timelineConfig.centerLatitude,
                lng: timelineConfig.centerLongitude,
            };
            const zoom = timelineConfig.zoom;

            if (timelineMapProvider === 'google' && timelineMap?.setCenter) {
                timelineMap.setCenter(center);
                timelineMap.setZoom(zoom);
            } else if (timelineMapProvider === 'leaflet' && timelineMap?.setView) {
                timelineMap.setView([center.lat, center.lng], zoom);
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
                        geodesic: true,
                        strokeColor: '#f59e0b',
                        strokeOpacity: 0,
                        strokeWeight: 3,
                        zIndex: 10,
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
                        smoothFactor: 0,
                        noClip: true,
                    }).addTo(timelineMap);
                    line.bindTooltip(title);
                    timelinePolylines.push(line);
                }
            });
        }

        function resolveMissingAddresses(addressLookups) {
            if (!addressLookups.length) {
                return;
            }

            addressLookups.forEach(function (lookup, index) {
                setTimeout(async function () {
                    const node = document.getElementById(lookup.id);
                    if (!node) return;

                    try {
                        const body = new URLSearchParams();
                        body.set('latitude', lookup.latitude);
                        body.set('longitude', lookup.longitude);
                        body.set('_token', timelineConfig.csrfToken);

                        const res = await fetch(timelineConfig.reverseGeocodeUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-CSRF-TOKEN': timelineConfig.csrfToken,
                            },
                            body,
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.address) {
                                node.innerHTML = `${escapeHtml(data.address)}<br><a href="javascript:void(0)" onclick="focusTimelinePoint(${lookup.latitude}, ${lookup.longitude})">View in map</a>`;
                                return;
                            }
                        }
                    } catch (e) {}

                    node.textContent = 'Address not found';
                }, index * 200);
            });
        }

        function overlayShell(contents, data = {}, gpsDistance = '-', directionsDistance = null) {
            const summaryHtml = `
                <div class="card radius-10 bg-primary mt-2 mb-3 shadow-lg">
                    <div class="card-header bg-primary text-white font-weight-bold fw-bold">${escapeHtml(data.employeeName || 'Employee Timeline')}</div>
                    <div class="card-body text-white p-3">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 fw-bold">Total tracked time</dt>
                            <dd class="col-sm-6 mb-2">${escapeHtml(data.totalTrackedTime || '00:00:00')}</dd>
                            <dt class="col-sm-6 fw-bold">Total attendance time</dt>
                            <dd class="col-sm-6 mb-2">${escapeHtml(data.totalAttendanceTime || '00:00:00')}</dd>
                            <dt class="col-sm-6 fw-bold">Total travelled distance</dt>
                            <dd class="col-sm-6 mb-2" id="distance">${escapeHtml(gpsDistance !== '-' ? gpsDistance : (data.totalKM !== undefined && data.totalKM !== null ? data.totalKM + ' KM' : '- KM'))}</dd>
                            <dt class="col-sm-6 fw-bold">Device information</dt>
                            <dd class="col-sm-6 mb-0">${escapeHtml(data.deviceInfo || '-')}</dd>
                        </dl>
                    </div>
                </div>
            `;

            return `
                <div class="timeline-overlay-shell">
                    ${summaryHtml}
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
            timelineGoogleRoutingDisabled = false;
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
            timelineActualRoutePolylineCount = 0;

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

        let activeInfoWindow = null;

        function itemActivityBadgeHtml(item) {
            if (item && item.activity_badge_html) {
                return item.activity_badge_html;
            }

            const code = String(item?.activity_code || item?.activity || item?.type || '').toUpperCase();
            if (code.includes('CHECK_IN') || code === 'CHECKIN') return '<span class="badge bg-soft-primary text-primary me-1">🏁 Check In</span>';
            if (code.includes('CHECK_OUT') || code === 'CHECKOUT') return '<span class="badge bg-soft-danger text-danger me-1">🏁 Check Out</span>';
            if (code.includes('STILL') || code === 'STATIONARY') return '<span class="badge bg-soft-secondary text-secondary me-1">⏸ Still</span>';
            if (code.includes('WALK')) return '<span class="badge bg-soft-success text-success me-1">🟢 Walking</span>';
            if (code.includes('RUN')) return '<span class="badge bg-soft-info text-info me-1">🏃 Running</span>';
            if (code.includes('VEHICLE') || code.includes('TRAVELLING')) return '<span class="badge bg-soft-purple text-purple me-1">🚗 Vehicle</span>';

            return '<span class="badge bg-soft-light text-dark me-1">❓ Unknown</span>';
        }

        function markerPopupHtml(point) {
            const time = escapeHtml(point.recorded_at || point.recordedAt || point.startTime || '-');
            const address = escapeHtml(point.address || 'Address loading or unavailable');
            const accuracy = accuracyHtml(point.accuracy);
            const activity = itemActivityBadgeHtml(point);
            const speed = point.speed_kmh !== undefined ? `${point.speed_kmh} km/h` : (point.speed !== undefined && point.speed !== null ? `${Math.round(point.speed * 3.6)} km/h` : '0 km/h');
            const battery = (point.batteryPercentage !== undefined && point.batteryPercentage !== null) ? `${point.batteryPercentage}%` : (point.battery_percentage !== undefined ? `${point.battery_percentage}%` : '-');
            const roadSnapped = point.road_snapped !== undefined ? (point.road_snapped ? 'Yes' : 'No') : (timelineConfig.roadRouteEnabled && timelineConfig.hasGoogleMapsKey ? 'Yes' : 'No');
            const offline = (point.isOffline || point.is_offline) ? 'Yes' : 'No';
            const mockGps = (point.isMockLocation || point.is_mock_location) ? 'Yes' : 'No';

            return `
                <div class="timeline-marker-popup" style="max-width: 250px; font-size: 11px; line-height: 1.4;">
                    <div class="fw-bold border-bottom pb-1 mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="ti ti-clock me-1"></i>${time}</span>
                        <span>${activity}</span>
                    </div>
                    <div class="mb-1 text-muted small">${address}</div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <span class="badge bg-light text-dark border">Acc: ${accuracy}</span>
                        <span class="badge bg-light text-dark border">Spd: ${escapeHtml(speed)}</span>
                        <span class="badge bg-light text-dark border">Bat: ${escapeHtml(battery)}</span>
                        <span class="badge bg-light text-dark border">Road: ${escapeHtml(roadSnapped)}</span>
                        <span class="badge bg-light text-dark border">Offline: ${escapeHtml(offline)}</span>
                        <span class="badge bg-light text-dark border">Mock: ${escapeHtml(mockGps)}</span>
                    </div>
                </div>
            `;
        }

        function bindMarkerPopup(marker, point) {
            const popupContent = markerPopupHtml(point);
            if (timelineMapProvider === 'google') {
                const infoWindow = new google.maps.InfoWindow({ content: popupContent });
                marker.addListener('click', function () {
                    if (activeInfoWindow) {
                        activeInfoWindow.close();
                    }
                    infoWindow.open(timelineMap, marker);
                    activeInfoWindow = infoWindow;
                    focusTimelinePoint(point.lat || point.latitude, point.lng || point.longitude);
                });
            } else if (timelineMapProvider === 'leaflet') {
                marker.bindPopup(popupContent);
                marker.on('click', function () {
                    focusTimelinePoint(point.lat || point.latitude, point.lng || point.longitude);
                });
            }
        }

        function accuracyHtml(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="badge bg-soft-secondary text-secondary">Accuracy Unknown</span>';
            }

            const num = Number(value);
            if (!Number.isFinite(num) || num < 0) {
                return '<span class="badge bg-soft-secondary text-secondary">Accuracy Unknown</span>';
            }

            if (num <= 5) {
                return `<span class="badge bg-soft-success text-success">Accuracy ${num.toFixed(0)}m</span>`;
            }

            if (num <= 15) {
                return `<span class="badge bg-soft-primary text-primary">Accuracy ${num.toFixed(0)}m</span>`;
            }

            if (num <= 30) {
                return `<span class="badge bg-soft-warning text-warning">Accuracy ${num.toFixed(0)}m</span>`;
            }

            return `<span class="badge bg-soft-danger text-danger">Accuracy ${num.toFixed(0)}m</span>`;
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
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=geometry,routes&loading=async&callback=initEmployeeTrackingMap&v=weekly" onerror="window.gm_authFailure()"></script>
    @endif
@endpush
