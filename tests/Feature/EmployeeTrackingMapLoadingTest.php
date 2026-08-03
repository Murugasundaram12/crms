<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTrackingMapLoadingTest extends TestCase
{
    use RefreshDatabase;

    private const SEEDED_GOOGLE_MAPS_API_KEY = 'AIzaSyDNZMjI6BykptQrTCZJiPX2iEwBmd9UZUU';

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed for the configured in-memory feature test database.');
        }

        parent::setUp();
    }

    public function test_demo_google_maps_key_is_rejected_and_shows_small_leaflet_badge_only(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);

        AppSetting::query()->updateOrCreate(
            ['key' => 'map_provider'],
            ['group' => 'map', 'value' => 'google', 'type' => 'string', 'is_public' => true]
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'google_maps_api_key'],
            ['group' => 'map', 'value' => self::SEEDED_GOOGLE_MAPS_API_KEY, 'type' => 'string', 'is_public' => true]
        );

        $response = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->get(route('tracking.index'));

        $response->assertOk();
        $response->assertSee('OpenStreetMap view', false);
        $response->assertSee('timeline-map-provider-badge', false);
        $response->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', false);
        $response->assertDontSee('https://maps.googleapis.com/maps/api/js', false);
        $response->assertDontSee(self::SEEDED_GOOGLE_MAPS_API_KEY, false);
        $response->assertDontSee('Google Maps API key missing', false);
        $response->assertDontSee('google_maps_api_key', false);
    }

    public function test_env_google_maps_key_is_used_when_app_setting_contains_seeded_key(): void
    {
        config(['services.google.maps_api_key' => 'real-browser-key']);

        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);

        AppSetting::query()->updateOrCreate(
            ['key' => 'map_provider'],
            ['group' => 'map', 'value' => 'google', 'type' => 'string', 'is_public' => true]
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'google_maps_api_key'],
            ['group' => 'map', 'value' => self::SEEDED_GOOGLE_MAPS_API_KEY, 'type' => 'string', 'is_public' => true]
        );

        $response = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->get(route('tracking.index'));

        $response->assertOk();
        $response->assertSee('https://maps.googleapis.com/maps/api/js?key=real-browser-key&libraries=geometry&callback=initEmployeeTrackingMap&v=weekly', false);
        $response->assertDontSee('OpenStreetMap view', false);
        $response->assertDontSee('timeline-map-provider-badge', false);
        $response->assertDontSee(self::SEEDED_GOOGLE_MAPS_API_KEY, false);
    }

    public function test_missing_google_maps_key_does_not_generate_google_script_and_keeps_leaflet_available(): void
    {
        $viewer = User::factory()->create(['role' => 'Super Admin', 'status' => 'active']);

        AppSetting::query()->updateOrCreate(
            ['key' => 'map_provider'],
            ['group' => 'map', 'value' => 'google', 'type' => 'string', 'is_public' => true]
        );

        $response = $this->actingAs($viewer)
            ->withoutMiddleware()
            ->get(route('tracking.index'));

        $response->assertOk();
        $response->assertSee('OpenStreetMap view', false);
        $response->assertSee('timeline-map-provider-badge', false);
        $response->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', false);
        $response->assertDontSee('https://maps.googleapis.com/maps/api/js', false);
        $response->assertDontSee('Google Maps API key missing', false);
        $response->assertDontSee('google_maps_api_key', false);
    }
}
