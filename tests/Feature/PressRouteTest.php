<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PressRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_press_center_page_with_trailing_slash_is_accessible(): void
    {
        CmsPage::factory()->create([
            'title' => 'Пресс-центр',
            'url' => '/press',
            'page_status' => 3,
            'page_of_type' => 1,
            'template' => 'default',
        ]);

        $this->get('/press/')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page->component('GenericPage'),
        );
    }
}
