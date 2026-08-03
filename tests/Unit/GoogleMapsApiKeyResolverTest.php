<?php

namespace Tests\Unit;

use App\Services\GoogleMapsApiKeyResolver;
use PHPUnit\Framework\TestCase;

class GoogleMapsApiKeyResolverTest extends TestCase
{
    public function test_demo_key_is_rejected(): void
    {
        $this->assertSame(
            '',
            GoogleMapsApiKeyResolver::resolve(GoogleMapsApiKeyResolver::SEEDED_GOOGLE_MAPS_API_KEY, '')
        );
    }

    public function test_valid_env_fallback_key_is_accepted_when_setting_contains_demo_key(): void
    {
        $this->assertSame(
            'real-browser-key',
            GoogleMapsApiKeyResolver::resolve(GoogleMapsApiKeyResolver::SEEDED_GOOGLE_MAPS_API_KEY, 'real-browser-key')
        );
    }

    public function test_real_app_setting_key_wins_over_env_fallback(): void
    {
        $this->assertSame(
            'real-setting-key',
            GoogleMapsApiKeyResolver::resolve('real-setting-key', 'real-env-key')
        );
    }
}
