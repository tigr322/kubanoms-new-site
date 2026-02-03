<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feed_returns_news_items_in_descending_date_order(): void
    {
        CmsPage::factory()->create([
            'title' => 'Вторая новость',
            'url' => '/news/second.html',
            'template' => 'news',
            'page_status' => 3,
            'page_of_type' => 2,
            'publication_date' => '2026-02-01 12:00:00',
            'content' => '<p>Текст</p>',
        ]);

        CmsPage::factory()->create([
            'title' => 'Первая новость',
            'url' => '/news/first.html',
            'template' => 'news',
            'page_status' => 3,
            'page_of_type' => 2,
            'publication_date' => '2026-02-02 12:00:00',
            'content' => '<p>Текст</p>',
        ]);

        // Not a real news item (wrong template) - should be ignored.
        CmsPage::factory()->create([
            'title' => 'Не должно попасть в RSS',
            'url' => '/fake.html',
            'template' => 'default',
            'page_status' => 3,
            'page_of_type' => 2,
            'publication_date' => '2026-02-03 12:00:00',
        ]);

        $this->get('/rss.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('<rss', false)
            ->assertSeeInOrder([
                '02.02.2026. Первая новость',
                '01.02.2026. Вторая новость',
            ], false)
            ->assertDontSee('Не должно попасть в RSS', false);
    }
}
