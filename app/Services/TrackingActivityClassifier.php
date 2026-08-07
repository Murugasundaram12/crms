<?php

namespace App\Services;

class TrackingActivityClassifier
{
    public const ACTIVITY_CHECK_IN = 'CHECK_IN';
    public const ACTIVITY_CHECK_OUT = 'CHECK_OUT';
    public const ACTIVITY_STILL = 'STILL';
    public const ACTIVITY_WALKING = 'WALKING';
    public const ACTIVITY_RUNNING = 'RUNNING';
    public const ACTIVITY_IN_VEHICLE = 'IN_VEHICLE';
    public const ACTIVITY_UNKNOWN = 'UNKNOWN';

    /**
     * Classify point activity using existing speed, distance, time, and raw activity patterns.
     */
    public function classify(
        ?string $type = null,
        ?string $activity = null,
        ?float $speedMps = null,
        ?float $distanceMeters = null,
        ?int $elapsedSeconds = null
    ): string {
        $normalizedType = strtolower((string) $type);

        if (in_array($normalizedType, ['checkin', 'checked_in', 'attendance_check_in'], true)) {
            return self::ACTIVITY_CHECK_IN;
        }

        if (in_array($normalizedType, ['checkout', 'checked_out', 'attendance_check_out'], true)) {
            return self::ACTIVITY_CHECK_OUT;
        }

        $speed = $speedMps;

        if (($speed === null || $speed < 0) && $distanceMeters !== null && $elapsedSeconds !== null && $elapsedSeconds > 0) {
            $speed = $distanceMeters / $elapsedSeconds;
        }

        if ($speed !== null && $speed >= 0) {
            return match (true) {
                $speed <= 0.8 => self::ACTIVITY_STILL,
                $speed <= 2.2 => self::ACTIVITY_WALKING,
                $speed <= 5.5 => self::ACTIVITY_RUNNING,
                default => self::ACTIVITY_IN_VEHICLE,
            };
        }

        $normalizedActivity = strtolower((string) $activity);

        return match (true) {
            in_array($normalizedActivity, ['activitytype.still', 'still', 'stationary'], true) || $normalizedType === 'still' => self::ACTIVITY_STILL,
            in_array($normalizedActivity, ['activitytype.walking', 'walking', 'walk'], true) || $normalizedType === 'walk' || $normalizedType === 'walking' => self::ACTIVITY_WALKING,
            in_array($normalizedActivity, ['activitytype.running', 'running', 'run'], true) => self::ACTIVITY_RUNNING,
            in_array($normalizedActivity, ['activitytype.in_vehicle', 'in_vehicle', 'vehicle', 'travelling'], true) || $normalizedType === 'vehicle' || $normalizedType === 'travelling' => self::ACTIVITY_IN_VEHICLE,
            default => self::ACTIVITY_UNKNOWN,
        };
    }

    /**
     * Get badge payload for an activity code.
     */
    public function badge(string $activityCode): array
    {
        return match ($activityCode) {
            self::ACTIVITY_CHECK_IN => [
                'code' => self::ACTIVITY_CHECK_IN,
                'label' => 'Check In',
                'emoji' => '🏁',
                'badge_class' => 'bg-soft-primary text-primary',
                'html' => '🏁 Check In',
            ],
            self::ACTIVITY_CHECK_OUT => [
                'code' => self::ACTIVITY_CHECK_OUT,
                'label' => 'Check Out',
                'emoji' => '🏁',
                'badge_class' => 'bg-soft-danger text-danger',
                'html' => '🏁 Check Out',
            ],
            self::ACTIVITY_STILL => [
                'code' => self::ACTIVITY_STILL,
                'label' => 'Still',
                'emoji' => '⏸',
                'badge_class' => 'bg-soft-secondary text-secondary',
                'html' => '⏸ Still',
            ],
            self::ACTIVITY_WALKING => [
                'code' => self::ACTIVITY_WALKING,
                'label' => 'Walking',
                'emoji' => '🟢',
                'badge_class' => 'bg-soft-success text-success',
                'html' => '🟢 Walking',
            ],
            self::ACTIVITY_RUNNING => [
                'code' => self::ACTIVITY_RUNNING,
                'label' => 'Running',
                'emoji' => '🏃',
                'badge_class' => 'bg-soft-info text-info',
                'html' => '🏃 Running',
            ],
            self::ACTIVITY_IN_VEHICLE => [
                'code' => self::ACTIVITY_IN_VEHICLE,
                'label' => 'Vehicle',
                'emoji' => '🚗',
                'badge_class' => 'bg-soft-purple text-purple',
                'html' => '🚗 Vehicle',
            ],
            default => [
                'code' => self::ACTIVITY_UNKNOWN,
                'label' => 'Unknown',
                'emoji' => '❓',
                'badge_class' => 'bg-soft-light text-dark',
                'html' => '❓ Unknown',
            ],
        };
    }
}
