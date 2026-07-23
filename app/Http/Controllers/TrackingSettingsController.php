<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\GpsTrackingValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class TrackingSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = collect($this->definitions())
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => $this->settingValue($key, $definition['default'], $definition['type']),
            ])
            ->all();

        return view('pages.employee_tracking.settings', [
            'settings' => $settings,
            'sections' => $this->sections(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge($this->requestWithCurrentDefaults($request));

        $validated = $request->validate($this->rules());
        $validated = $this->withDerivedSettings($validated);
        $definitions = $this->definitions();

        foreach ($validated as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $definitions[$key]['group'],
                    'value' => $this->stringValue($value, $definitions[$key]['type']),
                    'type' => $definitions[$key]['type'],
                    'description' => $definitions[$key]['description'],
                    'is_public' => true,
                ]
            );
        }

        GpsTrackingValidationService::clearCachedSettings();
        Artisan::call('cache:clear');

        return redirect()
            ->route('tracking.settings')
            ->with('success', 'Tracking settings updated successfully.');
    }

    private function sections(): array
    {
        return [
            'Mobile Location Update Interval' => [
                'location_update_interval',
                'location_update_interval_type',
            ],
            'Offline Check Time' => [
                'offline_check_time',
                'offline_check_time_type',
            ],
            'Map Settings' => [
                'map_provider',
                'map_center_latitude',
                'map_center_longitude',
                'map_zoom_level',
            ],
            'Distance Unit' => [
                'distance_unit',
            ],
            'Offline Tracking Module' => [
                'offline_tracking_enabled',
            ],
        ];
    }

    private function rules(): array
    {
        return [
            'tracking_enabled' => ['nullable', 'boolean'],
            'live_location_enabled' => ['nullable', 'boolean'],
            'timeline_enabled' => ['nullable', 'boolean'],
            'location_update_interval' => ['required', 'integer', 'min:1', 'max:1440'],
            'location_update_interval_type' => ['required', 'in:seconds,minutes'],
            'background_tracking_required' => ['nullable', 'boolean'],
            'minimum_accuracy' => ['required', 'numeric', 'min:1', 'max:5000'],
            'minimum_distance_meters' => ['required', 'numeric', 'min:0', 'max:5000'],
            'maximum_speed_kmph' => ['required', 'numeric', 'min:1', 'max:500'],
            'mock_location_allowed' => ['nullable', 'boolean'],
            'offline_tracking_enabled' => ['nullable', 'boolean'],
            'allow_sync_after_checkout' => ['nullable', 'boolean'],
            'max_offline_sync_age_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'bulk_upload_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'map_provider' => ['required', 'in:google,leaflet'],
            'map_center_latitude' => ['required', 'numeric', 'between:-90,90'],
            'map_center_longitude' => ['required', 'numeric', 'between:-180,180'],
            'map_zoom_level' => ['required', 'integer', 'min:1', 'max:22'],
            'distance_unit' => ['required', 'in:km,miles'],
            'default_route_mode' => ['required', 'in:actual,road'],
            'actual_gps_route_enabled' => ['nullable', 'boolean'],
            'road_route_enabled' => ['nullable', 'boolean'],
            'show_offline_points' => ['nullable', 'boolean'],
            'show_low_signal_points' => ['nullable', 'boolean'],
            'show_gaps' => ['nullable', 'boolean'],
            'offline_check_time' => ['required', 'integer', 'min:1', 'max:1440'],
            'offline_check_time_type' => ['required', 'in:seconds,minutes,hours'],
            'large_gap_minutes' => ['required', 'numeric', 'min:1', 'max:1440'],
            'large_gap_distance_meters' => ['required', 'numeric', 'min:1', 'max:100000'],
            'low_signal_threshold' => ['required', 'integer', 'min:0', 'max:5'],
            'show_ignored_reason_count' => ['nullable', 'boolean'],
        ];
    }

    private function definitions(): array
    {
        return [
            'tracking_enabled' => $this->definition('tracking', true, 'boolean', 'Enable employee tracking.'),
            'live_location_enabled' => $this->definition('tracking', true, 'boolean', 'Enable live location views.'),
            'timeline_enabled' => $this->definition('tracking', true, 'boolean', 'Enable employee tracking timeline.'),
            'location_update_interval' => $this->definition('tracking', 15, 'integer', 'Mobile GPS update interval value.'),
            'location_update_interval_type' => $this->definition('tracking', 'seconds', 'string', 'Mobile GPS update interval unit.'),
            'tracking_interval_seconds' => $this->definition('tracking', 15, 'integer', 'Computed GPS update interval in seconds.'),
            'background_tracking_required' => $this->definition('tracking', true, 'boolean', 'Require background tracking after check-in.'),
            'minimum_accuracy' => $this->definition('tracking', 50, 'float', 'Maximum accepted GPS accuracy in meters.'),
            'max_accuracy_meters' => $this->definition('tracking', 50, 'float', 'Maximum raw GPS accuracy accepted by mobile API.'),
            'gps_max_accuracy_metres' => $this->definition('tracking', 50, 'float', 'Maximum GPS accuracy accepted by validator.'),
            'timeline_max_accuracy_meters' => $this->definition('tracking', 50, 'float', 'Maximum GPS accuracy accepted by timeline.'),
            'minimum_distance_meters' => $this->definition('tracking', 30, 'float', 'Minimum movement distance in meters.'),
            'gps_min_distance_metres' => $this->definition('tracking', 30, 'float', 'Minimum validator movement distance in meters.'),
            'timeline_minimum_distance_meters' => $this->definition('tracking', 30, 'float', 'Minimum timeline movement distance in meters.'),
            'maximum_speed_kmph' => $this->definition('tracking', 120, 'float', 'Maximum allowed GPS speed in km/h.'),
            'gps_max_speed_mps' => $this->definition('tracking', 33.3333, 'float', 'Maximum allowed GPS speed in m/s.'),
            'timeline_max_computed_speed_kmh' => $this->definition('tracking', 120, 'float', 'Maximum timeline computed speed in km/h.'),
            'mock_location_allowed' => $this->definition('tracking', false, 'boolean', 'Allow mock locations.'),
            'offline_tracking_enabled' => $this->definition('tracking', true, 'boolean', 'Allow offline queue sync.'),
            'allow_sync_after_checkout' => $this->definition('tracking', true, 'boolean', 'Allow queued points to sync after checkout if recorded inside attendance time.'),
            'max_offline_sync_age_hours' => $this->definition('tracking', 72, 'integer', 'Maximum offline sync age in hours.'),
            'bulk_upload_limit' => $this->definition('tracking', 100, 'integer', 'Maximum mobile bulk upload points.'),
            'map_provider' => $this->definition('map', 'google', 'string', 'Map provider.'),
            'map_center_latitude' => $this->definition('map', 11.016844, 'float', 'Default map center latitude.'),
            'map_center_longitude' => $this->definition('map', 76.955832, 'float', 'Default map center longitude.'),
            'map_zoom_level' => $this->definition('map', 12, 'integer', 'Default map zoom level.'),
            'distance_unit' => $this->definition('map', 'km', 'string', 'Distance display unit.'),
            'default_route_mode' => $this->definition('tracking', 'actual', 'string', 'Default route mode.'),
            'actual_gps_route_enabled' => $this->definition('tracking', true, 'boolean', 'Enable Actual GPS route mode.'),
            'road_route_enabled' => $this->definition('tracking', true, 'boolean', 'Enable Estimated Road route mode.'),
            'show_offline_points' => $this->definition('tracking', true, 'boolean', 'Show offline synced points.'),
            'show_low_signal_points' => $this->definition('tracking', true, 'boolean', 'Show low signal points.'),
            'show_gaps' => $this->definition('tracking', true, 'boolean', 'Show route gap indicators.'),
            'offline_check_time' => $this->definition('tracking', 15, 'integer', 'Online/offline check value.'),
            'offline_check_time_type' => $this->definition('tracking', 'minutes', 'string', 'Online/offline check unit.'),
            'online_threshold_seconds' => $this->definition('tracking', 900, 'integer', 'Computed online/offline threshold in seconds.'),
            'large_gap_minutes' => $this->definition('tracking', 10, 'float', 'Large route gap time threshold.'),
            'gps_max_inactive_gap_seconds' => $this->definition('tracking', 600, 'integer', 'Computed inactive route gap threshold in seconds.'),
            'large_gap_distance_meters' => $this->definition('tracking', 2000, 'float', 'Large route gap distance threshold.'),
            'low_signal_threshold' => $this->definition('tracking', 2, 'integer', 'Low signal warning threshold.'),
            'show_ignored_reason_count' => $this->definition('tracking', true, 'boolean', 'Show ignored GPS reason count in debug metrics.'),
        ];
    }

    private function withDerivedSettings(array $validated): array
    {
        foreach ($this->rules() as $key => $_rules) {
            if (! array_key_exists($key, $validated)) {
                $validated[$key] = false;
            }
        }

        $validated['tracking_interval_seconds'] = $this->secondsFrom(
            (int) $validated['location_update_interval'],
            (string) $validated['location_update_interval_type']
        );
        $validated['online_threshold_seconds'] = $this->secondsFrom(
            (int) $validated['offline_check_time'],
            (string) $validated['offline_check_time_type']
        );
        $validated['gps_max_inactive_gap_seconds'] = (int) round(((float) $validated['large_gap_minutes']) * 60);
        $validated['max_accuracy_meters'] = $validated['minimum_accuracy'];
        $validated['gps_max_accuracy_metres'] = $validated['minimum_accuracy'];
        $validated['timeline_max_accuracy_meters'] = $validated['minimum_accuracy'];
        $validated['gps_min_distance_metres'] = $validated['minimum_distance_meters'];
        $validated['timeline_minimum_distance_meters'] = $validated['minimum_distance_meters'];
        $validated['gps_max_speed_mps'] = ((float) $validated['maximum_speed_kmph']) / 3.6;
        $validated['timeline_max_computed_speed_kmh'] = $validated['maximum_speed_kmph'];

        return $validated;
    }

    private function settingValue(string $key, mixed $default, string $type): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return match ($type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'float' => (float) $setting->value,
            default => $setting->value,
        };
    }

    private function requestWithCurrentDefaults(Request $request): array
    {
        $values = [];

        foreach ($this->definitions() as $key => $definition) {
            if ($request->has($key)) {
                continue;
            }

            $values[$key] = $this->settingValue($key, $definition['default'], $definition['type']);
        }

        return $values;
    }

    private function stringValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'integer' => (string) (int) $value,
            'float' => rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.'),
            default => (string) $value,
        };
    }

    private function secondsFrom(int $value, string $type): int
    {
        return match ($type) {
            'minutes' => $value * 60,
            'hours' => $value * 3600,
            default => $value,
        };
    }

    private function definition(string $group, mixed $default, string $type, string $description): array
    {
        return compact('group', 'default', 'type', 'description');
    }
}
