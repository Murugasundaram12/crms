<?php

namespace App\Services;

class GoogleMapsApiKeyResolver
{
    public const SEEDED_GOOGLE_MAPS_API_KEY = 'AIzaSyDNZMjI6BykptQrTCZJiPX2iEwBmd9UZUU';

    public static function resolve(?string $settingKey, ?string $fallbackKey): string
    {
        $settingKey = trim((string) $settingKey);

        if (self::isUsable($settingKey)) {
            return $settingKey;
        }

        $fallbackKey = trim((string) $fallbackKey);

        return self::isUsable($fallbackKey) ? $fallbackKey : '';
    }

    public static function isUsable(string $key): bool
    {
        return $key !== '' && $key !== self::SEEDED_GOOGLE_MAPS_API_KEY;
    }
}
