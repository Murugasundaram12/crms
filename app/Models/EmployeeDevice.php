<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDevice extends Model
{
    protected $fillable = [
        'employee_id',
        'device_id',
        'device_name',
        'device_type',
        'brand',
        'board',
        'sdk_version',
        'model',
        'messaging_token',
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
        'last_seen_at',
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
        'last_seen_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
