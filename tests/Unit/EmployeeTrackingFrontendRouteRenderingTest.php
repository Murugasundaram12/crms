<?php

namespace Tests\Unit;

use Tests\TestCase;

class EmployeeTrackingFrontendRouteRenderingTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = file_get_contents(resource_path('views/pages/employee_tracking/index.blade.php'));
    }

    public function test_actual_route_uses_backend_polyline_segments_only(): void
    {
        $this->assertStringContainsString('const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);', $this->source);
        $this->assertStringContainsString('function buildMovementPathsFromSegments(segments)', $this->source);
        $this->assertStringContainsString('drawRoutePolyline(routePath);', $this->source);
        $this->assertStringContainsString("return 'actual';", $this->source);
        $this->assertStringNotContainsString('drawDirectionsSegments(', $this->source);
        $this->assertStringNotContainsString("if (routeMode === 'road'", $this->source);
    }

    public function test_each_backend_segment_is_drawn_as_one_solid_blue_polyline(): void
    {
        $this->assertStringContainsString('for (const routePath of movementPaths)', $this->source);
        $this->assertStringContainsString('new google.maps.Polyline', $this->source);
        $this->assertStringContainsString("strokeColor: '#0d47ff'", $this->source);
        $this->assertStringContainsString('strokeOpacity: .95', $this->source);
        $this->assertStringContainsString('strokeWeight: 4', $this->source);
        $this->assertStringNotContainsString('path.isOfflineSegment', $this->source);
        $this->assertStringNotContainsString("strokeOpacity: latLngs.isOfflineSegment", $this->source);
    }

    public function test_route_markers_are_numbered_and_sampled_from_route_segments(): void
    {
        $this->assertStringContainsString('const routeMarkers = buildRouteMarkersFromSegments(movementPaths);', $this->source);
        $this->assertStringContainsString('function drawNumberedRouteMarker(point)', $this->source);
        $this->assertStringContainsString("point.isEnd ? '#ef4444' : '#16a34a'", $this->source);
        $this->assertStringContainsString('numberedPinSvg(label, color)', $this->source);
        $this->assertStringNotContainsString('green_circle.png', $this->source);
        $this->assertStringNotContainsString('red_circle.png', $this->source);
    }

    public function test_map_redraw_clears_old_layers_and_ignores_stale_ajax_responses(): void
    {
        $this->assertStringContainsString('clearTimelineMap();', $this->source);
        $this->assertStringContainsString('timelineMarkers.forEach', $this->source);
        $this->assertStringContainsString('timelinePolylines.forEach', $this->source);
        $this->assertStringContainsString('timelineDirectionsRenderers.forEach', $this->source);
        $this->assertStringContainsString('const loadToken = ++timelineLoadToken;', $this->source);
        $this->assertStringContainsString('if (loadToken !== timelineLoadToken)', $this->source);
    }

    public function test_map_fits_visible_route_or_resets_for_empty_timelines(): void
    {
        $this->assertStringContainsString('fitTimelineMapToPoints(boundsPoints.concat(routeMarkers).concat(attendanceMarkers));', $this->source);
        $this->assertStringContainsString('resetTimelineMapView();', $this->source);
        $this->assertStringContainsString('firstTimelineCoordinate(items)', $this->source);
        $this->assertStringContainsString('timelineMap.fitBounds(bounds, 72);', $this->source);
        $this->assertStringContainsString("maxZoom: 16", $this->source);
    }

    public function test_empty_timeline_reset_uses_configured_center_and_zoom_for_both_map_providers(): void
    {
        $this->assertStringContainsString('lat: timelineConfig.centerLatitude', $this->source);
        $this->assertStringContainsString('lng: timelineConfig.centerLongitude', $this->source);
        $this->assertStringContainsString('const zoom = timelineConfig.zoom;', $this->source);
        $this->assertStringContainsString('timelineMap.setCenter(center);', $this->source);
        $this->assertStringContainsString('timelineMap.setZoom(zoom);', $this->source);
        $this->assertStringContainsString('timelineMap.setView([center.lat, center.lng], zoom);', $this->source);
        $this->assertStringNotContainsString('timelineConfig.center.lat', $this->source);
        $this->assertStringNotContainsString('timelineConfig.center.lng', $this->source);
    }

    public function test_frontend_javascript_has_no_hardcoded_latitude_or_longitude_fallbacks(): void
    {
        $liveSource = file_get_contents(resource_path('views/pages/employee_tracking/live_location.blade.php'));

        $this->assertStringContainsString("centerLatitude: Number(@json(\$mapSettings['center_latitude']))", $this->source);
        $this->assertStringContainsString("centerLongitude: Number(@json(\$mapSettings['center_longitude']))", $this->source);
        $this->assertStringContainsString("centerLatitude: Number(@json(\$mapSettings['center_latitude']))", $liveSource);
        $this->assertStringContainsString("centerLongitude: Number(@json(\$mapSettings['center_longitude']))", $liveSource);

        foreach ([$this->source, $liveSource] as $frontendSource) {
            $this->assertStringNotContainsString('20.5937', $frontendSource);
            $this->assertStringNotContainsString('78.9629', $frontendSource);
            $this->assertStringNotContainsString('11.016844', $frontendSource);
            $this->assertStringNotContainsString('76.955832', $frontendSource);
            $this->assertStringNotContainsString('9.9252', $frontendSource);
            $this->assertStringNotContainsString('78.1198', $frontendSource);
        }
    }

    public function test_ajax_error_clears_and_resets_timeline_map(): void
    {
        $this->assertStringContainsString('if (!response.ok)', $this->source);
        $this->assertStringContainsString('clearTimelineMap();' . PHP_EOL . '                resetTimelineMapView();', $this->source);
        $this->assertStringContainsString("overlayShell('Unable to load timeline.')", $this->source);
    }

    public function test_google_maps_script_is_conditional_and_leaflet_badge_is_non_technical(): void
    {
        $this->assertStringContainsString("@if (filled(\$googleMapsKey))", $this->source);
        $this->assertStringContainsString('https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}', $this->source);
        $this->assertStringContainsString('initLeafletTimelineMap(centerLat, centerLng, zoomLevel);', $this->source);
        $this->assertStringContainsString('timeline-map-provider-badge', $this->source);
        $this->assertStringContainsString('OpenStreetMap view', $this->source);
        $this->assertStringNotContainsString('Google Maps API key missing', $this->source);
        $this->assertStringNotContainsString('Add <code>google_maps_api_key</code>', $this->source);
    }

    public function test_normal_timeline_shows_compact_user_summary_only(): void
    {
        $this->assertStringContainsString('Employee', $this->source);
        $this->assertStringContainsString('Total tracked time', $this->source);
        $this->assertStringContainsString('Total attendance time', $this->source);
        $this->assertStringContainsString('Total travelled distance', $this->source);
        $this->assertStringContainsString('Device information', $this->source);
        $this->assertStringContainsString('timeline-partial-badge', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Tracking coverage</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Raw GPS rows</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Accepted points</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Rejected points</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Route segments</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Offline queue count</dt>', $this->source);
    }

    public function test_attendance_card_retains_user_facing_session_details(): void
    {
        $this->assertStringContainsString('Attendance', $this->source);
        $this->assertStringContainsString('Check In', $this->source);
        $this->assertStringContainsString('Check Out', $this->source);
        $this->assertStringContainsString('Check-in GPS marker not available', $this->source);
        $this->assertStringContainsString('Check-out GPS marker not available', $this->source);
        $this->assertStringNotContainsString('Average accuracy', $this->source);
    }
}
