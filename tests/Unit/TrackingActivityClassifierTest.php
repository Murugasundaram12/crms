<?php

namespace Tests\Unit;

use App\Services\TrackingActivityClassifier;
use PHPUnit\Framework\TestCase;

class TrackingActivityClassifierTest extends TestCase
{
    private TrackingActivityClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new TrackingActivityClassifier();
    }

    public function test_it_classifies_check_in_and_check_out_types(): void
    {
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_CHECK_IN, $this->classifier->classify('checkIn'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_CHECK_IN, $this->classifier->classify('checked_in'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_CHECK_OUT, $this->classifier->classify('checkOut'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_CHECK_OUT, $this->classifier->classify('checked_out'));
    }

    public function test_it_classifies_by_speed_in_mps(): void
    {
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_STILL, $this->classifier->classify('travelling', null, 0.4));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_WALKING, $this->classifier->classify('travelling', null, 1.5));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_RUNNING, $this->classifier->classify('travelling', null, 3.5));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_IN_VEHICLE, $this->classifier->classify('travelling', null, 15.0));
    }

    public function test_it_calculates_speed_from_distance_and_elapsed_time_when_speed_is_unavailable(): void
    {
        // 100 meters in 20 seconds = 5.0 m/s => RUNNING
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_RUNNING, $this->classifier->classify(null, null, null, 100.0, 20));

        // 500 meters in 20 seconds = 25.0 m/s => IN_VEHICLE
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_IN_VEHICLE, $this->classifier->classify(null, null, null, 500.0, 20));
    }

    public function test_it_falls_back_to_raw_activity_string(): void
    {
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_WALKING, $this->classifier->classify(null, 'activitytype.walking'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_STILL, $this->classifier->classify(null, 'still'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_IN_VEHICLE, $this->classifier->classify(null, 'in_vehicle'));
        $this->assertEquals(TrackingActivityClassifier::ACTIVITY_UNKNOWN, $this->classifier->classify(null, null));
    }

    public function test_it_returns_badge_payload_with_emojis(): void
    {
        $badge = $this->classifier->badge(TrackingActivityClassifier::ACTIVITY_WALKING);

        $this->assertEquals('WALKING', $badge['code']);
        $this->assertEquals('Walking', $badge['label']);
        $this->assertEquals('🟢', $badge['emoji']);
        $this->assertStringContainsString('Walking', $badge['html']);
    }
}
