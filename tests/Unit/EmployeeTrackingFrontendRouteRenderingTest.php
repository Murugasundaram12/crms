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

    public function test_road_mode_uses_directions_and_does_not_also_draw_raw_polyline(): void
    {
        $this->assertStringContainsString("if (routeMode === 'road' && timelineMapProvider === 'google')", $this->source);
        $this->assertStringContainsString('await drawDirectionsSegments(directionsSegments, renderToken);', $this->source);
        $this->assertStringContainsString("return;\n            }\n\n            for (const routePath of movementPaths)", $this->source);
    }

    public function test_actual_mode_draws_validated_polyline_segments_without_directions_service(): void
    {
        $this->assertStringContainsString("defaultRouteMode: @json(request('route_mode', \$mapSettings['default_route_mode'] ?? 'actual'))", $this->source);
        $this->assertStringContainsString('Actual GPS Route', $this->source);
        $this->assertStringContainsString('Partial Actual GPS Route', $this->source);
        $this->assertStringContainsString('const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);', $this->source);
        $this->assertStringContainsString('drawRoutePolyline(routePath);', $this->source);
        $this->assertStringContainsString('new google.maps.Polyline', $this->source);
        $this->assertStringNotContainsString("if (routeMode === 'markers')", $this->source);
    }

    public function test_google_maps_uses_estimated_road_route_only_when_tracking_is_reliable(): void
    {
        $this->assertStringContainsString("defaultRouteMode: @json(request('route_mode', \$mapSettings['default_route_mode'] ?? 'actual'))", $this->source);
        $this->assertStringContainsString("timelineConfig.defaultRouteMode === 'road'", $this->source);
        $this->assertStringContainsString('hasDirectionsSegments(data) && isRoadRouteReliable(data)', $this->source);
        $this->assertStringContainsString("return 'actual';", $this->source);
        $this->assertStringContainsString('function isRoadRouteReliable(data = {})', $this->source);
        $this->assertStringContainsString('coverage < 60', $this->source);
        $this->assertStringContainsString('gapCount > 0', $this->source);
        $this->assertStringNotContainsString('id="timelineRouteMode"', $this->source);
        $this->assertStringNotContainsString("getElementById('timelineRouteMode')", $this->source);
    }

    public function test_google_directions_keeps_waypoint_order(): void
    {
        $this->assertStringContainsString('optimizeWaypoints: false', $this->source);
        $this->assertStringContainsString("google.maps.TravelMode[segment.travel_mode || 'DRIVING']", $this->source);
        $this->assertStringContainsString('google.maps.TravelMode.DRIVING', $this->source);
    }

    public function test_old_polylines_and_directions_renderers_are_cleared_before_redraw(): void
    {
        $this->assertStringContainsString('clearTimelineMap();', $this->source);
        $this->assertStringContainsString('timelinePolylines.forEach', $this->source);
        $this->assertStringContainsString('timelineDirectionsRenderers.forEach', $this->source);
        $this->assertStringContainsString('renderer.setMap(null);', $this->source);
    }

    public function test_frontend_uses_backend_separated_directions_segments_for_road_mode(): void
    {
        $this->assertStringContainsString('Estimated Road Route', $this->source);
        $this->assertStringContainsString('const roadSegments = data.directionsSegments || [];', $this->source);
        $this->assertStringNotContainsString('buildFieldStyleRoadSegments', $this->source);
        $this->assertStringNotContainsString('route_style: \'field_estimated\'', $this->source);
        $this->assertStringNotContainsString('selectRoadWaypoints', $this->source);
        $this->assertStringContainsString('data.directionsSegments || []', $this->source);
        $this->assertStringContainsString('drawDirectionsSegments(directionsSegments, renderToken)', $this->source);
        $this->assertStringNotContainsString('drawDirectionsSegments(data.polylineSegments', $this->source);
    }

    public function test_frontend_displays_field_style_basic_summary_card(): void
    {
        $this->assertStringContainsString('Employee', $this->source);
        $this->assertStringContainsString('Total tracked time', $this->source);
        $this->assertStringContainsString('Total attendance time', $this->source);
        $this->assertStringContainsString('Total travelled distance', $this->source);
        $this->assertStringContainsString('Device information', $this->source);
        $this->assertStringContainsString('timelineGpsDistance', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Tracking coverage</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Missing tracking time</dt>', $this->source);
        $this->assertStringNotContainsString('<dt class="col-6">Route segments</dt>', $this->source);
    }

    public function test_frontend_displays_tracking_health_metrics(): void
    {
        $this->assertStringContainsString('Showing partial actual GPS route only; missing periods are disconnected.', $this->source);
        $this->assertStringContainsString('Estimated road route is hidden because sparse GPS points can create wrong loops or jumps.', $this->source);
        $this->assertStringContainsString('Tracking started late after check-in', $this->source);
    }

    public function test_frontend_marks_offline_segments_and_missing_gaps_separately(): void
    {
        $this->assertStringContainsString('path.isOfflineSegment', $this->source);
        $this->assertStringContainsString("strokeColor: latLngs.isOfflineSegment && timelineConfig.showOfflinePoints ? '#7c3aed' : '#0000FF'", $this->source);
        $this->assertStringContainsString('function drawGapIndicators(gaps)', $this->source);
        $this->assertStringContainsString("strokeColor: '#f59e0b'", $this->source);
        $this->assertStringContainsString('Missing tracking gap:', $this->source);
    }

    public function test_frontend_displays_attendance_accuracy_and_battery_separately(): void
    {
        $this->assertStringNotContainsString('Check-in time', $this->source);
        $this->assertStringNotContainsString('Average accuracy', $this->source);
        $this->assertStringContainsString('Check-in GPS marker not available', $this->source);
        $this->assertStringContainsString('Check-out GPS marker not available', $this->source);
    }

    public function test_map_markers_are_icon_only_with_route_start_and_end_pins(): void
    {
        $this->assertStringNotContainsString('label: {', $this->source);
        $this->assertStringNotContainsString('${markerNumber}</div>', $this->source);
        $this->assertStringContainsString('drawRouteBoundaryMarkers', $this->source);
        $this->assertStringContainsString('green_circle.png', $this->source);
        $this->assertStringContainsString('red_circle.png', $this->source);
    }
}
