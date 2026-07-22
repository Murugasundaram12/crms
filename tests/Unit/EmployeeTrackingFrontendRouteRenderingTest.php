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
        $this->assertStringContainsString("defaultRouteMode: @json(request('route_mode', 'actual'))", $this->source);
        $this->assertStringContainsString('Actual GPS Route', $this->source);
        $this->assertStringContainsString('const movementPaths = buildMovementPathsFromSegments(data.polylineSegments);', $this->source);
        $this->assertStringContainsString('drawRoutePolyline(routePath);', $this->source);
        $this->assertStringContainsString('new google.maps.Polyline', $this->source);
    }

    public function test_google_maps_defaults_to_actual_gps_route_for_official_tracking(): void
    {
        $this->assertStringNotContainsString("filled(\$googleMapsKey) ? 'road' : 'actual'", $this->source);
        $this->assertStringContainsString("routeModeSelect.value = timelineConfig.defaultRouteMode === 'road' ? 'road' : 'actual';", $this->source);
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

    public function test_frontend_uses_backend_directions_segments_instead_of_flattening_polyline_segments_for_road_mode(): void
    {
        $this->assertStringContainsString('Estimated Road Route', $this->source);
        $this->assertStringContainsString('data.directionsSegments || []', $this->source);
        $this->assertStringContainsString('drawDirectionsSegments(directionsSegments, renderToken)', $this->source);
        $this->assertStringNotContainsString('drawDirectionsSegments(data.polylineSegments', $this->source);
    }

    public function test_frontend_displays_gps_and_directions_distances_separately(): void
    {
        $this->assertStringContainsString('Actual GPS distance', $this->source);
        $this->assertStringContainsString('Estimated road distance', $this->source);
        $this->assertStringContainsString('timelineGpsDistance', $this->source);
        $this->assertStringContainsString('timelineDirectionsDistance', $this->source);
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
