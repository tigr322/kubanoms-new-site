<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_name_is_configured(): void
    {
        $this->assertIsString(config('app.name'));
        $this->assertNotEmpty(config('app.name'));
    }
}
