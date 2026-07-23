<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationTracking extends Model
{
    protected $fillable = [
        'attendance_id',
        'employee_id',
        'device_id',
        'client_uuid',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'bearing',
        'activity',
        'is_gps_on',
        'is_wifi_on',
        'is_mock_location',
        'is_offline',
        'battery_percentage',
        'signal_strength',
        'type',
        'recorded_at',
        'is_ignored',
        'ignored_reason',
        'processed_at',
        'segment_index',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'bearing' => 'decimal:2',
        'is_gps_on' => 'boolean',
        'is_wifi_on' => 'boolean',
        'is_mock_location' => 'boolean',
        'is_offline' => 'boolean',
        'battery_percentage' => 'integer',
        'recorded_at' => 'datetime',
        'is_ignored' => 'boolean',
        'processed_at' => 'datetime',
        'segment_index' => 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
