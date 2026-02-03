<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_pages(): void
    {
        $press = CmsPage::factory()->create([
            'title' => 'Пресс-центр',
            'url' => '/press',
            'page_status' => 3,
            'page_of_type' => 1,
            'content' => '<p>Новости и документы</p>',
        ]);

        CmsPage::factory()->create([
            'title' => 'Другая страница',
            'url' => '/other',
            'page_status' => 3,
            'page_of_type' => 1,
            'content' => '<p>Текст</p>',
        ]);

        $this->get('/search?q=%D0%9F%D1%80%D0%B5%D1%81%D1%81')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('Search')
                ->where('query', 'Пресс')
                ->has('results', 1)
                ->where('results.0.id', 'page-'.$press->id)
                ->where('results.0.title', 'Пресс-центр')
                ->where('results.0.url', '/press'),
        );
    }
}
