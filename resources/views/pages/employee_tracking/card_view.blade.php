@extends('layouts.app')

@section('title', 'Card View')

@push('styles')
    <style>
        .tracking-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 14px;
        }

        .tracking-employee-card {
            border-left: 4px solid #dc2626;
        }

        .tracking-employee-card.online {
            border-left-color: #16a34a;
        }

        .tracking-avatar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #3730a3;
            font-weight: 800;
        }

        .tracking-device-icons {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
        <div>
            <h4 class="mb-1">Card View</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Card View</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="btn-group" role="group" aria-label="Tracking status">
                <button type="button" class="btn btn-primary tracking-filter-btn active" data-filter="all">All <span class="badge bg-light text-primary" id="allCount">0</span></button>
                <button type="button" class="btn btn-outline-primary tracking-filter-btn" data-filter="on_duty">On Duty <span class="badge bg-success" id="onlineCount">0</span></button>
                <button type="button" class="btn btn-outline-primary tracking-filter-btn" data-filter="inactive">Inactive <span class="badge bg-warning" id="inactiveCount">0</span></button>
                <button type="button" class="btn btn-outline-primary tracking-filter-btn" data-filter="off_duty">Off Duty <span class="badge bg-danger" id="offDutyCount">0</span></button>
            </div>
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" checked id="autoRefreshSwitch">
                <label class="form-check-label" for="autoRefreshSwitch">Auto refresh</label>
            </div>
        </div>
    </div>

    <div id="trackingCards" class="tracking-cards-grid"></div>
@endsection

@push('scripts')
    <script>
        const cardViewConfig = {
            dataUrl: @json(route('dashboard/cardViewAjax')),
            initialCards: @json($cards),
        };

        let cardRefreshTimer = null;
        let allTrackingCards = cardViewConfig.initialCards || [];
        let activeCardFilter = 'all';

        document.addEventListener('DOMContentLoaded', function () {
            renderTrackingCards(allTrackingCards);
            startCardRefresh();

            document.getElementById('autoRefreshSwitch')?.addEventListener('change', function () {
                this.checked ? startCardRefresh() : stopCardRefresh();
            });

            document.querySelectorAll('.tracking-filter-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    activeCardFilter = this.dataset.filter || 'all';
                    updateFilterButtons();
                    renderTrackingCards(allTrackingCards);
                });
            });
        });

        async function loadTrackingCards() {
            const response = await fetch(cardViewConfig.dataUrl, {
                headers: {'Accept': 'application/json'},
            });
            allTrackingCards = await response.json();
            renderTrackingCards(allTrackingCards);
        }

        function renderTrackingCards(cards) {
            const online = cards.filter(function (card) {
                return card.isOnline && !card.attendanceOutAt;
            }).length;
            const inactive = cards.filter(function (card) {
                return !card.isOnline && card.attendanceInAt && !card.attendanceOutAt;
            }).length;
            const offDuty = cards.filter(function (card) {
                return card.attendanceOutAt || !card.attendanceInAt;
            }).length;

            document.getElementById('allCount').textContent = cards.length;
            document.getElementById('onlineCount').textContent = online;
            document.getElementById('inactiveCount').textContent = inactive;
            document.getElementById('offDutyCount').textContent = offDuty;

            const visibleCards = cards.filter(cardMatchesActiveFilter);

            if (!visibleCards.length) {
                document.getElementById('trackingCards').innerHTML = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">
                            <i class="ti ti-id-badge-2 fs-1 d-block mb-2"></i>
                            No employees found for this filter.
                        </div>
                    </div>
                `;
                return;
            }

            document.getElementById('trackingCards').innerHTML = visibleCards.map(cardHtml).join('');
        }

        function cardMatchesActiveFilter(card) {
            if (activeCardFilter === 'on_duty') {
                return card.isOnline && !card.attendanceOutAt;
            }

            if (activeCardFilter === 'inactive') {
                return !card.isOnline && card.attendanceInAt && !card.attendanceOutAt;
            }

            if (activeCardFilter === 'off_duty') {
                return card.attendanceOutAt || !card.attendanceInAt;
            }

            return true;
        }

        function updateFilterButtons() {
            document.querySelectorAll('.tracking-filter-btn').forEach(function (button) {
                const isActive = button.dataset.filter === activeCardFilter;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-primary', !isActive);
                button.classList.toggle('active', isActive);
            });
        }

        function cardHtml(card) {
            const initials = String(card.name || 'E')
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map(function (part) { return part[0]; })
                .join('')
                .toUpperCase();
            const status = card.attendanceOutAt ? 'Off Duty' : (card.isOnline ? 'On Duty' : 'Inactive');
            const statusClass = card.attendanceOutAt ? 'danger' : (card.isOnline ? 'success' : 'warning');
            const mapUrl = card.latitude && card.longitude
                ? `https://www.google.com/maps/place/${card.latitude},${card.longitude}`
                : null;

            return `
                <div class="card border-0 shadow-sm tracking-employee-card ${card.isOnline && !card.attendanceOutAt ? 'online' : ''}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                            <div class="d-flex gap-3 align-items-center">
                                <span class="tracking-avatar">${escapeHtml(initials)}</span>
                                <div>
                                    <h5 class="mb-1">${escapeHtml(card.name || 'Employee')}</h5>
                                    <div class="text-muted small">${escapeHtml(card.phoneNumber || card.designation || '-')}</div>
                                </div>
                            </div>
                            <span class="badge bg-${statusClass}-transparent text-${statusClass}">${status}</span>
                        </div>

                        <div class="tracking-device-icons mb-3">
                            ${metricHtml('Battery', batteryText(card.batteryLevel), batteryClass(card.batteryLevel), 'ti-battery')}
                            ${metricHtml('GPS', card.isGpsOn ? 'On' : 'Off', card.isGpsOn ? 'success' : 'danger', 'ti-map-pin')}
                            ${metricHtml('Accuracy', card.accuracy ? `${card.accuracy}m` : '-', 'secondary', 'ti-focus')}
                        </div>

                        <ul class="list-group list-group-flush border rounded">
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span class="text-muted">Attendance</span>
                                <span class="text-end">
                                    <strong>IN ${escapeHtml(card.attendanceInAt || '-')} ${card.attendanceOutAt ? ` / OUT ${escapeHtml(card.attendanceOutAt)}` : ''}</strong>
                                    <a class="d-block mt-1" href="${escapeHtml(card.timelineUrl || '#')}">Timeline <i class="ti ti-calendar-check ms-1"></i></a>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span class="text-muted">Location</span>
                                ${mapUrl ? `<a href="${mapUrl}" target="_blank" rel="noopener">Open in maps</a>` : '<span>-</span>'}
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span class="text-muted">Last updated</span>
                                <span>${escapeHtml(card.updatedAt || '-')}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span class="text-muted">Device</span>
                                <span class="text-end">${escapeHtml(card.deviceInfo || '-')}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            `;
        }

        function metricHtml(label, value, color, icon) {
            return `
                <div class="border rounded p-2">
                    <small class="text-muted d-block">${label}</small>
                    <strong class="text-${color}"><i class="ti ${icon} me-1"></i>${escapeHtml(value)}</strong>
                </div>
            `;
        }

        function batteryText(value) {
            return value === null || value === undefined ? '-%' : `${value}%`;
        }

        function batteryClass(value) {
            if (value === null || value === undefined) {
                return 'secondary';
            }
            if (value >= 40) {
                return 'success';
            }
            if (value >= 15) {
                return 'warning';
            }
            return 'danger';
        }

        function startCardRefresh() {
            stopCardRefresh();
            cardRefreshTimer = setInterval(loadTrackingCards, 15000);
        }

        function stopCardRefresh() {
            if (cardRefreshTimer) {
                clearInterval(cardRefreshTimer);
                cardRefreshTimer = null;
            }
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
