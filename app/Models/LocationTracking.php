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
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'bearing',
        'activity',
        'is_gps_on',
        'is_wifi_on',
        'is_mock_location',
        'battery_percentage',
        'signal_strength',
        'type',
        'recorded_at',
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
        'battery_percentage' => 'integer',
        'recorded_at' => 'datetime',
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
