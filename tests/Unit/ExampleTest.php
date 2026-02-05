<?php

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_app_name_is_configured(): void
    {
        $this->assertIsString(config('app.name'));
        $this->assertNotEmpty(config('app.name'));
    }

    public function test_app_url_is_valid(): void
    {
        $url = config('app.url');

        $this->assertIsString($url);
        $this->assertNotEmpty($url);
        $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL));

        $parts = parse_url($url);
        $this->assertIsArray($parts);
        $this->assertArrayHasKey('scheme', $parts);
        $this->assertArrayHasKey('host', $parts);
        $this->assertContains($parts['scheme'], ['http', 'https']);
    }
}
