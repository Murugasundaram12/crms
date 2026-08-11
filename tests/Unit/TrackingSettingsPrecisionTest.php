<?php

namespace Tests\Unit;

use App\Http\Controllers\TrackingSettingsController;
use ReflectionMethod;
use Tests\TestCase;

class TrackingSettingsPrecisionTest extends TestCase
{
    public function test_string_value_preserves_8_decimal_places_for_floats(): void
    {
        $controller = new TrackingSettingsController();
        $method = new ReflectionMethod(TrackingSettingsController::class, 'stringValue');
        $method->setAccessible(true);

        $lat = $method->invoke($controller, 18.41898377, 'float');
        $lng = $method->invoke($controller, 49.67194362, 'float');

        $this->assertEquals('18.41898377', $lat);
        $this->assertEquals('49.67194362', $lng);
    }
}
