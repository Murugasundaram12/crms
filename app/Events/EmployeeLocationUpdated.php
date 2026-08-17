<?php

namespace App\Events;

use App\Models\LocationTracking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public LocationTracking $tracking;

    public function __construct(LocationTracking $tracking)
    {
        $this->tracking = $tracking;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('employee-tracking'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.location.updated';
    }

    public function broadcastWith(): array
    {
        $employee = $this->tracking->relationLoaded('employee')
            ? $this->tracking->employee
            : $this->tracking->employee()->first();

        $recordedAt = $this->tracking->recorded_at
            ? $this->tracking->recorded_at->toISOString()
            : now()->toISOString();

        return [
            'id' => (int) $this->tracking->id,
            'employee_id' => (int) ($this->tracking->employee_id ?? $this->tracking->user_id ?? 0),
            'name' => $employee?->name ?? 'Employee',
            'latitude' => (float) $this->tracking->latitude,
            'longitude' => (float) $this->tracking->longitude,
            'accuracy' => $this->tracking->accuracy !== null ? (float) $this->tracking->accuracy : null,
            'status' => 'online',
            'online_status' => 'online',
            'battery_percentage' => $this->tracking->battery_percentage !== null ? (int) $this->tracking->battery_percentage : null,
            'is_gps_on' => (bool) $this->tracking->is_gps_on,
            'recorded_at' => $recordedAt,
            'updatedAt' => $recordedAt,
        ];
    }
}
