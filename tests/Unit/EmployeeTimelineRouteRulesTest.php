<?php

namespace Tests\Unit;

use App\Http\Controllers\EmployeeTrackingController;
use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Services\EmployeeTimelineBuilder;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeeTimelineRouteRulesTest extends TestCase
{
    public function test_large_inactive_gap_starts_a_new_polyline_segment(): void
    {
        $controller = new EmployeeTrackingController();
        $previous = $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', attendanceId: 1);
        $current = $this->point(11.000200, 77.000000, '2026-07-21 11:10:00', attendanceId: 1);

        $this->assertTrue($this->invoke($controller, 'shouldBreakTimelineSegment', [$previous, $current]));
    }

    public function test_separate_attendance_sessions_are_not_connected(): void
    {
        $controller = new EmployeeTrackingController();
        $previous = $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', attendanceId: 1);
        $current = $this->point(11.000200, 77.000000, '2026-07-21 10:01:00', attendanceId: 2);

        $this->assertTrue($this->invoke($controller, 'shouldBreakTimelineSegment', [$previous, $current]));
    }

    public function test_first_valid_point_returns_marker_data_without_polyline_segment(): void
    {
        $controller = new EmployeeTrackingController();
        $items = collect([
            [
                'type' => 'walk',
                'latitude' => 11.000000,
                'longitude' => 77.000000,
                'segmentBreakBefore' => false,
            ],
        ]);

        $this->assertSame([], $this->invoke($controller, 'polylineSegmentsFromItems', [$items]));
        $this->assertCount(0, $this->invoke($controller, 'polylinePointsFromItems', [$items]));
    }

    public function test_still_employee_points_do_not_create_route_lines_or_distance(): void
    {
        $controller = new EmployeeTrackingController();
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', activity: 'still', speed: 0, attendanceId: 1),
            $this->point(11.000100, 77.000000, '2026-07-21 10:01:00', activity: 'still', speed: 0, attendanceId: 1),
        ]);

        $items = $this->invoke($controller, 'timelineModuleItems', [$trackings]);

        $this->assertSame(['still', 'still'], $items->pluck('type')->all());
        $this->assertSame(0.0, (float) $items->sum('distance'));
        $this->assertSame([], $this->invoke($controller, 'polylineSegmentsFromItems', [$items]));
    }

    public function test_tracking_type_still_does_not_create_route_even_when_activity_is_walking(): void
    {
        $controller = new EmployeeTrackingController();
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', activity: 'walking', speed: 0, attendanceId: 1, trackingType: 'still'),
            $this->point(11.000100, 77.000000, '2026-07-21 10:01:00', activity: 'walking', speed: 0, attendanceId: 1, trackingType: 'still'),
        ]);

        $items = $this->invoke($controller, 'timelineModuleItems', [$trackings]);

        $this->assertSame(['still', 'still'], $items->pluck('type')->all());
        $this->assertSame([], $this->invoke($controller, 'polylineSegmentsFromItems', [$items]));
    }

    public function test_legacy_still_status_rows_keep_wifi_and_do_not_create_route_lines(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');

        $first = $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'ActivityType.STILL', speed: 0, attendanceId: 1, trackingType: 'still')
            ->setRelation('attendance', $attendance);
        $second = $this->point(11.000050, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'ActivityType.STILL', speed: 0, attendanceId: 1, trackingType: 'still')
            ->setRelation('attendance', $attendance);

        $first->is_wifi_on = true;
        $second->is_wifi_on = true;

        $timeline = $builder->build(collect([$first, $second]), $this->builderOptions());

        $this->assertSame(['still', 'still'], $timeline['items']->pluck('type')->all());
        $this->assertSame([true, true], $timeline['items']->pluck('isWifiOn')->all());
        $this->assertSame([], $timeline['polylineSegments']);
        $this->assertSame(0.0, (float) $timeline['gpsDistanceKm']);
    }

    public function test_travelling_type_with_still_activity_does_not_create_route(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(11.000020, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame(['still', 'still'], $timeline['items']->pluck('type')->all());
        $this->assertSame([], $timeline['polylineSegments']);
        $this->assertSame(0.0, (float) $timeline['gpsDistanceKm']);
    }

    public function test_legacy_travelling_rows_with_still_activity_use_coordinate_movement_for_route(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(11.000500, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(11.001000, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame(['still', 'vehicle', 'vehicle'], $timeline['items']->pluck('type')->all());
        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame([2, 3], collect($timeline['polylineSegments'][0]['points'])->pluck('id')->all());
    }

    public function test_low_speed_walking_jitter_does_not_create_route_lines(): void
    {
        $controller = new EmployeeTrackingController();
        $trackings = collect([
            $this->point(10.3676288, 77.9819904, '2026-07-21 14:38:50', id: 1, activity: 'walking', speed: 0.69, attendanceId: 25, trackingType: 'travelling'),
            $this->point(10.3678291, 77.9819371, '2026-07-21 14:39:34', id: 2, activity: 'walking', speed: 0.53, attendanceId: 25, trackingType: 'travelling'),
            $this->point(10.3676900, 77.9820700, '2026-07-21 14:40:20', id: 3, activity: 'walking', speed: 0.40, attendanceId: 25, trackingType: 'travelling'),
        ]);

        $items = $this->invoke($controller, 'timelineModuleItems', [$trackings]);

        $this->assertSame(0.0, (float) $items->sum('distance'));
        $this->assertSame([], $this->invoke($controller, 'polylineSegmentsFromItems', [$items]));
    }

    public function test_total_distance_uses_only_accepted_movement_points_in_same_session(): void
    {
        $controller = new EmployeeTrackingController();
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1),
            $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, attendanceId: 1),
            $this->point(11.000900, 77.000000, '2026-07-21 10:03:00', id: 4, activity: 'still', speed: 0, attendanceId: 1),
            $this->point(11.001200, 77.000000, '2026-07-21 10:04:00', id: 5, attendanceId: 2),
        ]);

        $items = $this->invoke($controller, 'timelineModuleItems', [$trackings]);

        $this->assertSame(0.06, round((float) $items->sum('distance'), 2));
        $this->assertCount(1, $this->invoke($controller, 'polylineSegmentsFromItems', [$items]));
        $this->assertCount(3, $this->invoke($controller, 'polylinePointsFromItems', [$items]));
    }

    public function test_old_tracking_rows_without_recorded_at_use_created_at_for_route_line(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 09:55:00', '2026-07-21 10:10:00');

        $first = $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)
            ->setRelation('attendance', $attendance);
        $second = $this->point(11.000500, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)
            ->setRelation('attendance', $attendance);

        $first->recorded_at = null;
        $second->recorded_at = null;

        $result = $builder->build(collect([$second, $first]), [
            'minimum_distance_meters' => 5,
            'max_accuracy_meters' => 50,
            'max_computed_speed_kmh' => 120,
        ]);

        $this->assertCount(1, $result['polylineSegments']);
        $this->assertCount(2, $result['polylineSegments'][0]['points']);
        $this->assertSame([1, 2], collect($result['polylineSegments'][0]['points'])->pluck('id')->all());
    }

    public function test_old_noisy_tracking_history_is_cleaned_without_connecting_bad_points(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendanceOne = $this->attendance(1, '2026-07-21 08:00:00', '2026-07-21 09:00:00');
        $attendanceTwo = $this->attendance(2, '2026-07-21 10:00:00', '2026-07-21 11:00:00');

        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 08:00:00', id: 1, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.000300, 77.000000, '2026-07-21 08:00:15', id: 2, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.000300, 77.000000, '2026-07-21 08:00:20', id: 3, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.000600, 77.000000, '2026-07-21 08:00:30', id: 4, activity: 'vehicle', accuracy: 80, attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.008000, 77.000000, '2026-07-21 08:00:45', id: 5, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.000700, 77.000000, '2026-07-21 08:01:00', id: 6, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendanceOne),
            $this->point(11.000710, 77.000010, '2026-07-21 08:01:10', id: 7, activity: 'walking', speed: 0.2, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendanceOne),
            $this->point(11.001000, 77.000000, '2026-07-21 08:20:00', id: 8, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.001500, 77.000000, '2026-07-21 08:20:15', id: 9, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.002000, 77.000000, '2026-07-21 10:00:00', id: 10, activity: 'vehicle', attendanceId: 2, deviceId: 'device-b')->setRelation('attendance', $attendanceTwo),
            $this->point(11.002500, 77.000000, '2026-07-21 10:00:15', id: 11, activity: 'vehicle', attendanceId: 2, deviceId: 'device-b')->setRelation('attendance', $attendanceTwo),
        ]);

        $result = $builder->build($trackings->reverse(), [
            ...$this->builderOptions(),
            'tracking_interval_seconds' => 15,
            'minimum_distance_meters' => 10,
            'max_accuracy_meters' => 50,
        ]);

        $this->assertSame(1, $result['rejectionReasons']['duplicate_location'] ?? 0);
        $this->assertSame(1, $result['rejectionReasons']['accuracy_exceeded'] ?? 0);
        $this->assertCount(3, $result['polylineSegments']);
        $this->assertSame([[1, 2], [8, 9], [10, 11]], collect($result['polylineSegments'])
            ->map(fn (array $segment) => collect($segment['unsimplified_points'])->pluck('id')->all())
            ->all());
    }

    public function test_authoritative_builder_returns_independent_segments_by_attendance_and_device(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendanceOne = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $attendanceTwo = $this->attendance(2, '2026-07-21 12:00:00', '2026-07-21 13:00:00');

        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1, trackingType: 'checked_in')->setRelation('attendance', $attendanceOne),
            $this->point(11.000100, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendanceOne),
            $this->point(11.000200, 77.000000, '2026-07-21 10:02:00', id: 3, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendanceOne),
            $this->point(11.000300, 77.000000, '2026-07-21 10:03:00', id: 4, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendanceOne),
            $this->point(11.000400, 77.000000, '2026-07-21 10:04:00', id: 5, attendanceId: 1, trackingType: 'checked_out')->setRelation('attendance', $attendanceOne),
            $this->point(11.001000, 77.000000, '2026-07-21 12:00:00', id: 6, attendanceId: 2, trackingType: 'checked_in')->setRelation('attendance', $attendanceTwo),
            $this->point(11.001100, 77.000000, '2026-07-21 12:01:00', id: 7, attendanceId: 2, trackingType: 'travelling')->setRelation('attendance', $attendanceTwo),
            $this->point(11.001200, 77.000000, '2026-07-21 12:02:00', id: 8, attendanceId: 2, trackingType: 'travelling', deviceId: 'device-b')->setRelation('attendance', $attendanceTwo),
        ]);

        $timeline = $builder->build($trackings, [
            'minimum_distance_meters' => 3,
            'max_accuracy_meters' => 10,
            'max_computed_speed_kmh' => 90,
        ]);

        $this->assertSame(['checkIn', 'walk', 'walk', 'still', 'checkOut', 'checkIn', 'walk', 'walk'], $timeline['items']->pluck('type')->all());
        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame(1, $timeline['polylineSegments'][0]['attendance_id']);
        $this->assertSame('device-a', $timeline['polylineSegments'][0]['device_id']);
        $this->assertCount(2, $timeline['polylineSegments'][0]['points']);
        $this->assertSame($timeline['polylinePoints']->all(), collect($timeline['polylineSegments'])->pluck('points')->flatten(1)->values()->all());
    }

    public function test_normal_ninety_degree_turn_is_preserved(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000120, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000120, 77.000120, '2026-07-21 10:02:00', id: 3, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame([], $timeline['rejectionReasons']);
        $this->assertSame([1, 2, 3], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
    }

    public function test_same_road_return_trip_stays_in_one_chronological_segment(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000900, 77.000000, '2026-07-21 10:03:00', id: 4, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:04:00', id: 5, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:05:00', id: 6, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000000, 77.000000, '2026-07-21 10:06:00', id: 7, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], collect($timeline['directionsSegments'][0]['source_point_ids'])->all());
    }

    public function test_rejected_point_does_not_break_the_route_before_the_next_accepted_point(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            // This bad fix is far enough to have caused a false segment break
            // when rejected raw rows were used as the previous route point.
            $this->point(11.050000, 77.050000, '2026-07-21 10:02:00', id: 3, accuracy: 80, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:03:00', id: 4, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame(1, $timeline['rejectionReasons']['accuracy_exceeded'] ?? 0);
        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame([1, 2, 4], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame(4, collect($timeline['polylineSegments'][0]['points'])->last()['id']);
        $this->assertSame(4, $timeline['directionsSegments'][0]['destination']['id']);
        $this->assertSame(4, $timeline['routeBlocks'][0]['source_point_ids'][2]);
    }

    public function test_all_field_management_tracking_scenarios(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 12:00:00');

        // Scenario 1: BUS -> BUS -> BUS
        $busOnly = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t1 = $builder->build($busOnly, $this->builderOptions());
        $this->assertCount(1, $t1['polylineSegments']);
        $this->assertSame([1, 2, 3], collect($t1['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame('DRIVING', $t1['directionsSegments'][0]['travel_mode']);

        // Scenario 2: BUS -> STILL -> BUS
        $busStillBus = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000310, 77.000010, '2026-07-21 10:05:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.001000, 77.000000, '2026-07-21 10:10:00', id: 4, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.001500, 77.000000, '2026-07-21 10:11:00', id: 5, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t2 = $builder->build($busStillBus, $this->builderOptions());
        $this->assertCount(2, $t2['polylineSegments']);
        $this->assertSame([1, 2], collect($t2['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([4, 5], collect($t2['polylineSegments'][1]['unsimplified_points'])->pluck('id')->all());

        // Scenario 3: BUS -> WALK -> WALK
        $busWalkWalk = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t3 = $builder->build($busWalkWalk, $this->builderOptions());
        $this->assertCount(1, $t3['polylineSegments']);
        $this->assertSame([1, 2, 3], collect($t3['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame('DRIVING', $t3['directionsSegments'][0]['travel_mode']);

        // Scenario 4: BUS -> STILL -> WALK
        $busStillWalk = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000310, 77.000010, '2026-07-21 10:05:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:10:00', id: 4, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000900, 77.000000, '2026-07-21 10:11:00', id: 5, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t4 = $builder->build($busStillWalk, $this->builderOptions());
        $this->assertCount(2, $t4['polylineSegments']);
        $this->assertSame([1, 2], collect($t4['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([4, 5], collect($t4['polylineSegments'][1]['unsimplified_points'])->pluck('id')->all());

        // Scenario 5: BUS -> WALK -> STILL -> WALK -> BUS
        $complexSeq = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000310, 77.000010, '2026-07-21 10:05:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:10:00', id: 4, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.001000, 77.000000, '2026-07-21 10:15:00', id: 5, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t5 = $builder->build($complexSeq, $this->builderOptions());
        $this->assertCount(2, $t5['polylineSegments']);
        $this->assertSame([1, 2], collect($t5['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([4, 5], collect($t5['polylineSegments'][1]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame(5, $t5['directionsSegments'][1]['destination']['id']);

        // Scenario 6: Accepted -> Rejected -> Accepted
        $rejSeq = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.050000, 77.050000, '2026-07-21 10:01:00', id: 2, accuracy: 90, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:02:00', id: 3, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t6 = $builder->build($rejSeq, $this->builderOptions());
        $this->assertSame([1, 3], collect($t6['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame(1, $t6['rejectionReasons']['accuracy_exceeded']);

        // Scenario 7: Check-in -> movement -> checkout
        $checkInPoint = $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, trackingType: 'checked_in')->setRelation('attendance', $attendance);
        $move1 = $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'walking', attendanceId: 1)->setRelation('attendance', $attendance);
        $move2 = $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'walking', attendanceId: 1)->setRelation('attendance', $attendance);
        $checkOutPoint = $this->point(11.000610, 77.000010, '2026-07-21 10:10:00', id: 4, trackingType: 'checked_out')->setRelation('attendance', $attendance);

        $t7 = $builder->build(collect([$checkInPoint, $move1, $move2, $checkOutPoint]), $this->builderOptions());
        $this->assertSame(['checkIn', 'walk', 'walk', 'checkOut'], $t7['items']->pluck('type')->all());
        $this->assertCount(1, $t7['polylineSegments']);
        $this->assertSame([2, 3], collect($t7['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame(3, $t7['directionsSegments'][0]['destination']['id']);

        // Scenario 8: BUS -> STILL -> STILL -> BUS
        $busStillStillBus = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000310, 77.000010, '2026-07-21 10:05:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.000310, 77.000010, '2026-07-21 10:08:00', id: 4, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.001000, 77.000000, '2026-07-21 10:12:00', id: 5, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.001500, 77.000000, '2026-07-21 10:13:00', id: 6, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t8 = $builder->build($busStillStillBus, $this->builderOptions());
        $this->assertCount(2, $t8['polylineSegments']);
        $this->assertSame([1, 2], collect($t8['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([5, 6], collect($t8['polylineSegments'][1]['unsimplified_points'])->pluck('id')->all());

        // Scenario 9: WALK -> BUS -> BUS
        $walkBusBus = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'walking', speed: 1.2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000600, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t9 = $builder->build($walkBusBus, $this->builderOptions());
        $this->assertCount(1, $t9['polylineSegments']);
        $this->assertSame([1, 2, 3], collect($t9['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame('DRIVING', $t9['directionsSegments'][0]['travel_mode']);

        // Scenario 10: BUS -> long GPS gap -> BUS
        $busGapBus = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000300, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.050000, 77.050000, '2026-07-21 11:30:00', id: 3, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.050300, 77.050000, '2026-07-21 11:31:00', id: 4, activity: 'in_vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);
        $t10 = $builder->build($busGapBus, $this->builderOptions());
        $this->assertCount(2, $t10['polylineSegments']);
        $this->assertSame([1, 2], collect($t10['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame([3, 4], collect($t10['polylineSegments'][1]['unsimplified_points'])->pluck('id')->all());
    }

    public function test_isolated_middle_point_spike_is_removed_from_route(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, accuracy: 4, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.001000, 77.001000, '2026-07-21 10:10:00', id: 2, accuracy: 8, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000060, 77.000060, '2026-07-21 10:20:00', id: 3, accuracy: 4, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame(1, $timeline['rejectionReasons']['angle_spike'] ?? 0);
        $this->assertSame([1, 3], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
        $this->assertSame('still', $timeline['items']->firstWhere('id', 2)['type']);
    }

    public function test_valid_u_turn_is_preserved_when_not_an_isolated_spike(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, accuracy: 4, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000200, 77.000000, '2026-07-21 10:03:00', id: 2, accuracy: 4, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000010, 77.000000, '2026-07-21 10:06:00', id: 3, accuracy: 4, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertArrayNotHasKey('angle_spike', $timeline['rejectionReasons']);
        $this->assertSame([1, 2, 3], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
    }

    public function test_post_still_nearby_walking_drift_does_not_start_a_new_route_segment(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000100, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000120, 77.000020, '2026-07-21 10:05:00', id: 3, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.000160, 77.000030, '2026-07-21 10:06:00', id: 4, activity: 'walking', speed: 0.3, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame('still', $timeline['items']->firstWhere('id', 4)['type']);
        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame([1, 2], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
    }

    public function test_still_activity_after_large_gap_does_not_create_route_line(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-24 20:20:00', '2026-07-24 20:50:00');

        $stillAfterMovement = $this->point(9.5838217, 77.7254526, '2026-07-24 20:30:12', id: 3, activity: 'still', attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance);
        $stillAfterGap = $this->point(9.5186910, 77.6328194, '2026-07-24 20:47:18', id: 4, activity: 'still', attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance);
        $stillAfterMovement->speed = null;
        $stillAfterGap->speed = null;

        $trackings = collect([
            $this->point(9.6243833, 77.7619617, '2026-07-24 20:23:24', id: 1, activity: 'travelling', attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(9.6218333, 77.7599683, '2026-07-24 20:23:43', id: 2, activity: 'travelling', attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $stillAfterMovement,
            $stillAfterGap,
        ]);

        $timeline = $builder->build($trackings, [
            ...$this->builderOptions(),
            'max_accuracy_meters' => 50,
            'max_inactive_gap_seconds' => 600,
        ]);

        $this->assertSame(['vehicle', 'vehicle', 'vehicle', 'still'], $timeline['items']->pluck('type')->all());
        $this->assertCount(1, $timeline['polylineSegments']);
        $this->assertSame([1, 2], collect($timeline['polylineSegments'][0]['unsimplified_points'])->pluck('id')->all());
    }

    public function test_directions_segment_uses_last_movement_point_as_destination_and_excludes_post_still_drift(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.003000, 77.000000, '2026-07-21 10:04:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.006000, 77.000000, '2026-07-21 10:08:00', id: 3, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.006010, 77.000010, '2026-07-21 10:10:00', id: 4, activity: 'still', speed: 0, attendanceId: 1, trackingType: 'still')->setRelation('attendance', $attendance),
            $this->point(11.006030, 77.000015, '2026-07-21 10:11:00', id: 5, activity: 'walking', speed: 0.2, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertCount(1, $timeline['directionsSegments']);
        $this->assertSame(1, $timeline['directionsSegments'][0]['origin']['id']);
        $this->assertSame(3, $timeline['directionsSegments'][0]['destination']['id']);
        $this->assertSame([1, 2, 3], $timeline['directionsSegments'][0]['source_point_ids']);
        $this->assertSame([2], collect($timeline['directionsSegments'][0]['waypoints'])->pluck('id')->all());
    }

    public function test_directions_waypoints_remain_chronological_and_limited(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect();

        for ($index = 0; $index < 30; $index++) {
            $trackings->push(
                $this->point(
                    11.000000 + ($index * 0.001),
                    77.000000 + (($index % 3) * 0.00001),
                    Carbon::parse('2026-07-21 10:00:00')->addMinute($index)->toDateTimeString(),
                    id: $index + 1,
                    attendanceId: 1
                )->setRelation('attendance', $attendance)
            );
        }

        $timeline = $builder->build($trackings, [
            ...$this->builderOptions(),
            'douglas_peucker_tolerance_meters' => 0,
        ]);

        $waypointIds = collect($timeline['directionsSegments'][0]['waypoints'])->pluck('id')->all();

        $this->assertLessThanOrEqual(23, count($waypointIds));
        $this->assertSame($waypointIds, collect($waypointIds)->sort()->values()->all());
        $this->assertSame(1, $timeline['directionsSegments'][0]['origin']['id']);
        $this->assertSame(30, $timeline['directionsSegments'][0]['destination']['id']);
    }

    public function test_separate_sessions_create_separate_directions_requests(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendanceOne = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $attendanceTwo = $this->attendance(2, '2026-07-21 12:00:00', '2026-07-21 13:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.006000, 77.000000, '2026-07-21 10:08:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendanceOne),
            $this->point(11.001000, 77.000000, '2026-07-21 12:00:00', id: 3, attendanceId: 2)->setRelation('attendance', $attendanceTwo),
            $this->point(11.007000, 77.000000, '2026-07-21 12:08:00', id: 4, attendanceId: 2)->setRelation('attendance', $attendanceTwo),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertCount(2, $timeline['directionsSegments']);
        $this->assertSame([1, 2], collect($timeline['directionsSegments'])->pluck('attendance_id')->all());
    }

    public function test_single_point_block_has_no_directions_route(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000010, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertSame([], $timeline['polylineSegments']);
        $this->assertSame([], $timeline['directionsSegments']);
    }

    public function test_short_valid_movement_block_still_creates_directions_route(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.002000, 77.000000, '2026-07-21 10:01:00', id: 2, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.003000, 77.000000, '2026-07-21 10:02:00', id: 3, activity: 'vehicle', attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertCount(1, $timeline['directionsSegments']);
        $this->assertSame([1, 2, 3], $timeline['directionsSegments'][0]['source_point_ids']);
        $this->assertLessThan(0.5, $timeline['polylineSegments'][0]['distance_km']);
    }

    public function test_douglas_peucker_reduces_redundant_points_without_changing_total_distance_source(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000080, 77.000000, '2026-07-21 10:01:00', id: 2, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000160, 77.000000, '2026-07-21 10:02:00', id: 3, attendanceId: 1)->setRelation('attendance', $attendance),
            $this->point(11.000240, 77.000000, '2026-07-21 10:03:00', id: 4, attendanceId: 1)->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, [
            ...$this->builderOptions(),
            'simplify_after_points' => 3,
        ]);

        $this->assertCount(4, $timeline['polylineSegments'][0]['unsimplified_points']);
        $this->assertCount(2, $timeline['polylineSegments'][0]['points']);
        $this->assertGreaterThan(0.02, $timeline['totalKM']);
    }

    public function test_direction_waypoints_preserves_near_endpoint_waypoints_on_short_routes(): void
    {
        $builder = app(EmployeeTimelineBuilder::class);
        $attendance = $this->attendance(1, '2026-07-21 10:00:00', '2026-07-21 11:00:00');

        // Short route: 3 points ~400m total span
        $trackings = collect([
            $this->point(11.000000, 77.000000, '2026-07-21 10:00:00', id: 1, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(11.001500, 77.000000, '2026-07-21 10:02:00', id: 2, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
            $this->point(11.003000, 77.000000, '2026-07-21 10:04:00', id: 3, attendanceId: 1, trackingType: 'travelling')->setRelation('attendance', $attendance),
        ]);

        $timeline = $builder->build($trackings, $this->builderOptions());

        $this->assertCount(1, $timeline['directionsSegments']);
        $waypoints = $timeline['directionsSegments'][0]['waypoints'];
        $this->assertCount(1, $waypoints);
        $this->assertSame(2, $waypoints[0]['id']);
    }

    private function invoke(EmployeeTrackingController $controller, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $arguments);
    }

    private function point(
        float $latitude,
        float $longitude,
        string $time,
        ?int $id = null,
        string $activity = 'walking',
        float $speed = 1,
        float $accuracy = 8,
        int $attendanceId = 1,
        string $trackingType = 'location',
        string $deviceId = 'device-a'
    ): LocationTracking {
        $timestamp = Carbon::parse($time);
        $tracking = new LocationTracking();
        $tracking->forceFill([
            'attendance_id' => $attendanceId,
            'employee_id' => 1,
            'device_id' => $deviceId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'activity' => $activity,
            'is_gps_on' => true,
            'is_mock_location' => false,
            'battery_percentage' => 80,
            'type' => $trackingType,
            'recorded_at' => $timestamp,
            'created_at' => $timestamp,
        ]);

        if ($id !== null) {
            $tracking->id = $id;
        }

        return $tracking;
    }

    private function attendance(int $id, string $checkInAt, string $checkOutAt): Attendance
    {
        $attendance = new Attendance();
        $attendance->forceFill([
            'id' => $id,
            'user_id' => 1,
            'attendance_date' => Carbon::parse($checkInAt)->toDateString(),
            'check_in_at' => Carbon::parse($checkInAt),
            'check_out_at' => Carbon::parse($checkOutAt),
            'status' => 'present',
        ]);

        return $attendance;
    }

    private function builderOptions(): array
    {
        return [
            'minimum_distance_meters' => 5,
            'max_accuracy_meters' => 8,
            'max_computed_speed_kmh' => 90,
            'max_bearing_change_degrees' => 45,
            'bearing_drift_distance_meters' => 10,
            'tracking_interval_seconds' => 300,
            'max_inactive_gap_seconds' => 3600,
            'douglas_peucker_tolerance_meters' => 3,
        ];
    }
}
