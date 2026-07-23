@extends('layouts.app')

@section('title', 'Tracking Debug Report')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tracking Debug Report</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tracking.index') }}">Employee Tracking</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Debug Report</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form id="trackingDebugForm" class="row g-3 align-items-end m-0">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="debugDate">Date</label>
                    <input type="date" id="debugDate" class="form-control" value="{{ request('date', now()->toDateString()) }}">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="debugEmployee">Employee</label>
                    <select id="debugEmployee" class="form-select">
                        <option value="">Please select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('employee') === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->email ? ' - ' . $employee->email : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-search me-1"></i> Check
                    </button>
                    <a href="{{ route('tracking.debug-report') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div id="debugReportContent">
        <div class="alert alert-info mb-0">Select employee and date to view tracking diagnostics.</div>
    </div>
@endsection

@push('scripts')
    <script>
        const debugConfig = {
            url: @json(route('tracking.debug-report.data')),
            csrf: @json(csrf_token()),
        };

        document.getElementById('trackingDebugForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            await loadTrackingDebugReport();
        });

        if (document.getElementById('debugEmployee').value) {
            loadTrackingDebugReport();
        }

        async function loadTrackingDebugReport() {
            const employeeId = document.getElementById('debugEmployee').value;
            const date = document.getElementById('debugDate').value;
            const container = document.getElementById('debugReportContent');

            if (!employeeId || !date) {
                container.innerHTML = '<div class="alert alert-warning mb-0">Employee and date are required.</div>';
                return;
            }

            container.innerHTML = '<div class="alert alert-info mb-0">Loading tracking diagnostics...</div>';

            const response = await fetch(debugConfig.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': debugConfig.csrf,
                },
                body: JSON.stringify({userId: employeeId, date}),
            });

            const data = await response.json();
            if (!response.ok) {
                container.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.message || 'Unable to load debug report.')}</div>`;
                return;
            }

            container.innerHTML = renderDebugReport(data);
        }

        function renderDebugReport(data) {
            const health = data.trackingHealth || {};
            const debug = data.gpsDebug || {};
            const reasons = health.ignored_reasons || debug.rejection_reason_count || {};
            const rows = debug.raw_point_diagnostics || [];
            const gaps = health.largest_gaps || [];

            return `
                <div class="row g-3 mb-4">
                    ${metricCard('Attendance time', data.totalAttendanceTime || '00:00:00')}
                    ${metricCard('Tracked time', data.totalTrackedTime || '00:00:00')}
                    ${metricCard('Coverage', formatPercent(health.tracking_coverage_percentage))}
                    ${metricCard('Missing time', health.missing_tracking_duration || '-')}
                    ${metricCard('First GPS delay', health.first_tracking_delay_duration || '-')}
                    ${metricCard('Last GPS at', health.last_tracking_at || '-')}
                    ${metricCard('Saved points', health.saved_points_count ?? debug.raw_point_count ?? 0)}
                    ${metricCard('Route points', health.route_points_count ?? 0)}
                    ${metricCard('Route segments', health.route_segments_count ?? 0)}
                    ${metricCard('Ignored points', health.ignored_points_count ?? debug.rejected_point_count ?? 0)}
                    ${metricCard('Offline synced', health.offline_synced_points_count ?? 0)}
                    ${metricCard('Low signal', health.low_signal_points_count ?? 0)}
                    ${metricCard('Accuracy', accuracySummary(health))}
                    ${metricCard('Battery', batterySummary(health))}
                    ${metricCard('Mock points', health.mock_points_count ?? 0)}
                    ${metricCard('Large gaps', health.gap_count ?? 0)}
                    ${metricCard('GPS distance', `${Number(data.gpsDistanceKm || 0).toFixed(2)} KM`)}
                    ${metricCard('Last sync', health.last_mobile_sync_at || '-')}
                    ${metricCard('First GPS at', health.first_tracking_at || '-')}
                    ${metricCard('Device', data.deviceInfo || '-')}
                </div>

                <div class="row g-4">
                    <div class="col-12 col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white"><h5 class="mb-0">Ignored Reasons</h5></div>
                            <div class="card-body">${reasonList(reasons)}</div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white"><h5 class="mb-0">Largest Gaps</h5></div>
                            <div class="card-body p-0">${gapTable(gaps)}</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white"><h5 class="mb-0">Raw GPS Diagnostics</h5></div>
                    <div class="card-body p-0">${diagnosticTable(rows)}</div>
                </div>
            `;
        }

        function metricCard(label, value) {
            return `
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">${escapeHtml(label)}</div>
                            <div class="fw-bold mt-1">${escapeHtml(value)}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function reasonList(reasons) {
            const entries = Object.entries(reasons || {});
            if (!entries.length) {
                return '<p class="text-muted mb-0">No ignored GPS points.</p>';
            }

            return `<div class="list-group list-group-flush">${entries.map(([reason, count]) => `
                <div class="list-group-item px-0 d-flex justify-content-between">
                    <span>${escapeHtml(reason)}</span>
                    <span class="badge bg-soft-danger text-danger">${escapeHtml(count)}</span>
                </div>
            `).join('')}</div>`;
        }

        function gapTable(gaps) {
            if (!gaps.length) {
                return '<div class="p-3 text-muted">No large gaps found.</div>';
            }

            return `
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Previous</th>
                                <th>Current</th>
                                <th>Gap</th>
                                <th>Distance</th>
                                <th>From</th>
                                <th>To</th>
                            </tr>
                        </thead>
                        <tbody>${gaps.map((gap) => `
                            <tr>
                                <td>${escapeHtml(gap.previous_recorded_at || '-')}</td>
                                <td>${escapeHtml(gap.current_recorded_at || '-')}</td>
                                <td>${escapeHtml(gap.gap_duration || '-')}</td>
                                <td>${Number(gap.distance_km || 0).toFixed(2)} KM</td>
                                <td>${coordinate(gap.previous_latitude, gap.previous_longitude)}</td>
                                <td>${coordinate(gap.current_latitude, gap.current_longitude)}</td>
                            </tr>
                        `).join('')}</tbody>
                    </table>
                </div>
            `;
        }

        function diagnosticTable(rows) {
            if (!rows.length) {
                return '<div class="p-3 text-muted">No GPS rows found.</div>';
            }

            return `
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Attendance</th>
                                <th>Recorded</th>
                                <th>Type</th>
                                <th>Activity</th>
                                <th>Coordinate</th>
                                <th>Accuracy</th>
                                <th>Speed</th>
                                <th>Status</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>${rows.map((row) => `
                            <tr>
                                <td>${escapeHtml(row.id || '-')}</td>
                                <td>${escapeHtml(row.attendance_id || '-')}</td>
                                <td>${escapeHtml(row.recorded_at || '-')}</td>
                                <td>${escapeHtml(row.type || '-')}</td>
                                <td>${escapeHtml(row.activity || '-')}</td>
                                <td>${coordinate(row.latitude, row.longitude)}</td>
                                <td>${row.accuracy ?? '-'}</td>
                                <td>${row.speed_kmph !== null && row.speed_kmph !== undefined ? Number(row.speed_kmph).toFixed(2) + ' km/h' : '-'}</td>
                                <td>${row.accepted ? '<span class="badge bg-soft-success text-success">Accepted</span>' : '<span class="badge bg-soft-danger text-danger">Ignored</span>'}</td>
                                <td>${escapeHtml(row.reason || '-')}</td>
                            </tr>
                        `).join('')}</tbody>
                    </table>
                </div>
            `;
        }

        function coordinate(latitude, longitude) {
            const lat = Number(latitude);
            const lng = Number(longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return '-';
            }

            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        function formatPercent(value) {
            const number = Number(value);
            return Number.isFinite(number) ? `${number.toFixed(2)}%` : '-';
        }

        function accuracySummary(health) {
            if (health.accuracy_min !== null && health.accuracy_min !== undefined && health.accuracy_max !== null && health.accuracy_max !== undefined) {
                return `${Number(health.accuracy_min).toFixed(0)}m - ${Number(health.accuracy_max).toFixed(0)}m`;
            }

            return '-';
        }

        function batterySummary(health) {
            if (health.battery_start !== null && health.battery_start !== undefined && health.battery_end !== null && health.battery_end !== undefined) {
                return `${health.battery_start}% - ${health.battery_end}%`;
            }

            return '-';
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
@endpush
