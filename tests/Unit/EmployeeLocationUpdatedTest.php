<?php

namespace Tests\Unit;

use App\Events\EmployeeLocationUpdated;
use App\Models\LocationTracking;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmployeeLocationUpdatedTest extends TestCase
{
    public function test_event_implements_should_broadcast_now(): void
    {
        $tracking = new LocationTracking();
        $event = new EmployeeLocationUpdated($tracking);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
    }

    public function test_event_broadcasts_on_private_employee_tracking_channel(): void
    {
        $tracking = new LocationTracking();
        $event = new EmployeeLocationUpdated($tracking);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-employee-tracking', $channels[0]->name);
    }

    public function test_event_broadcast_as_name_is_correct(): void
    {
        $tracking = new LocationTracking();
        $event = new EmployeeLocationUpdated($tracking);

        $this->assertEquals('employee.location.updated', $event->broadcastAs());
    }

    public function test_payload_contains_required_fields_and_preserves_float_precision(): void
    {
        $user = new User([
            'id' => 42,
            'name' => 'John Field Employee',
        ]);

        $tracking = new LocationTracking([
            'employee_id' => 42,
            'latitude' => 9.9252000,
            'longitude' => 78.1198000,
            'accuracy' => 12.50,
            'battery_percentage' => 88,
            'is_gps_on' => true,
            'recorded_at' => now(),
        ]);
        $tracking->id = 101;
        $tracking->setRelation('employee', $user);

        $event = new EmployeeLocationUpdated($tracking);
        $payload = $event->broadcastWith();

        $this->assertEquals(101, $payload['id']);
        $this->assertEquals(42, $payload['employee_id']);
        $this->assertEquals('John Field Employee', $payload['name']);
        $this->assertIsFloat($payload['latitude']);
        $this->assertIsFloat($payload['longitude']);
        $this->assertEquals(9.9252, $payload['latitude']);
        $this->assertEquals(78.1198, $payload['longitude']);
        $this->assertEquals(12.5, $payload['accuracy']);
        $this->assertEquals('online', $payload['status']);
        $this->assertEquals(88, $payload['battery_percentage']);
        $this->assertTrue($payload['is_gps_on']);
        $this->assertArrayHasKey('recorded_at', $payload);

        // Verify sensitive fields are omitted
        $this->assertArrayNotHasKey('device_id', $payload);
        $this->assertArrayNotHasKey('client_uuid', $payload);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('api_token', $payload);
    }

    public function test_event_can_be_faked_and_dispatched(): void
    {
        Event::fake([EmployeeLocationUpdated::class]);

        $tracking = new LocationTracking(['id' => 200, 'employee_id' => 1]);
        EmployeeLocationUpdated::dispatch($tracking);

        Event::assertDispatched(EmployeeLocationUpdated::class, function ($event) use ($tracking) {
            return $event->tracking->id === $tracking->id;
        });
    }
}
