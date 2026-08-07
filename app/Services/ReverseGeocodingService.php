<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ReverseGeocodingService
{
    public const CACHE_TTL_DAYS = 30;

    /**
     * Resolve reverse geocoded address using 30-day SHA1 cache key with fallback options.
     */
    public function reverseGeocode(float $latitude, float $longitude, ?string $googleMapsApiKey = null, ?string $fallbackAddress = null): string
    {
        $hashInput = round($latitude, 5) . '_' . round($longitude, 5);
        $cacheKey = 'gps_address_' . sha1($hashInput);

        $cachedAddress = Cache::get($cacheKey);
        if (filled($cachedAddress)) {
            return (string) $cachedAddress;
        }

        $apiKey = $googleMapsApiKey ?? config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY', ''));

        if (blank($apiKey)) {
            return $fallbackAddress ?? sprintf('%.5f, %.5f', $latitude, $longitude);
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey,
            ]);

            if ($response->ok()) {
                $results = $response->json('results', []);
                if (! empty($results) && isset($results[0]['formatted_address'])) {
                    $address = (string) $results[0]['formatted_address'];
                    Cache::put($cacheKey, $address, now()->addDays(self::CACHE_TTL_DAYS));

                    return $address;
                }
            }
        } catch (\Throwable) {
            // Suppress network errors and fallback cleanly
        }

        if (filled($fallbackAddress)) {
            return $fallbackAddress;
        }

        return sprintf('%.5f, %.5f', $latitude, $longitude);
    }
}
