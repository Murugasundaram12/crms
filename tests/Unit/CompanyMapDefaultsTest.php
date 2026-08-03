<?php

namespace Tests\Unit;

use App\Support\CompanyMapDefaults;
use PHPUnit\Framework\TestCase;

class CompanyMapDefaultsTest extends TestCase
{
    public function test_company_map_defaults_point_to_madurai(): void
    {
        $this->assertSame(9.9252, CompanyMapDefaults::CENTER_LATITUDE);
        $this->assertSame(78.1198, CompanyMapDefaults::CENTER_LONGITUDE);
        $this->assertSame(12, CompanyMapDefaults::ZOOM_LEVEL);
    }
}
