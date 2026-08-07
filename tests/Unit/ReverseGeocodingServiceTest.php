<?php

namespace Tests\Unit;

use App\Services\ReverseGeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReverseGeocodingServiceTest extends TestCase
{
    private ReverseGeocodingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReverseGeocodingService();
    }

    public function test_it_uses_sha1_hash_cache_key_and_returns_cached_address(): void
    {
        $lat = 13.0827;
        $lng = 80.2707;
        $cacheKey = 'gps_address_' . sha1(round($lat, 5) . '_' . round($lng, 5));

        Cache::put($cacheKey, 'Chennai Central, Tamil Nadu, India', now()->addDays(30));

        $result = $this->service->reverseGeocode($lat, $lng, 'mock_api_key');

        $this->assertEquals('Chennai Central, Tamil Nadu, India', $result);
    }

    public function test_it_fetches_address_from_google_api_on_cache_miss_and_stores_in_cache(): void
    {
        $lat = 12.9716;
        $lng = 77.5946;
        $cacheKey = 'gps_address_' . sha1(round($lat, 5) . '_' . round($lng, 5));

        Cache::forget($cacheKey);

        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    ['formatted_address' => 'Bengaluru, Karnataka, India'],
                ],
            ], 200),
        ]);

        $result = $this->service->reverseGeocode($lat, $lng, 'valid_google_key');

        $this->assertEquals('Bengaluru, Karnataka, India', $result);
        $this->assertEquals('Bengaluru, Karnataka, India', Cache::get($cacheKey));
    }

    public function test_it_falls_back_to_coordinates_or_provided_fallback_when_api_fails(): void
    {
        $lat = 10.0000;
        $lng = 20.0000;
        $cacheKey = 'gps_address_' . sha1(round($lat, 5) . '_' . round($lng, 5));

        Cache::forget($cacheKey);

        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([], 500),
        ]);

        $result = $this->service->reverseGeocode($lat, $lng, 'key', 'Previous Known Address');
        $this->assertEquals('Previous Known Address', $result);

        $fallbackCoords = $this->service->reverseGeocode($lat, $lng, 'key');
        $this->assertEquals('10.00000, 20.00000', $fallbackCoords);
    }
}
