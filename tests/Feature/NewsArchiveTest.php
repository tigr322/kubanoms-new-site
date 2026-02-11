<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_archive_lists_published_news(): void
    {
        CmsPage::factory()->create([
            'title' => 'Архив новостей',
            'url' => '/newslist',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'content' => '<p>Описание архива.</p>',
        ]);

        CmsPage::factory()->create([
            'title' => 'Старая новость',
            'url' => '/newslist/old.html',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::NEWS->value,
            'publication_date' => now()->setDate(2024, 1, 10),
        ]);

        CmsPage::factory()->create([
            'title' => 'Новая новость',
            'url' => '/newslist/new.html',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::NEWS->value,
            'publication_date' => now()->setDate(2024, 2, 5),
        ]);

        CmsPage::factory()->create([
            'title' => 'Черновик',
            'url' => '/newslist/draft.html',
            'page_status' => PageStatus::DRAFT->value,
            'page_of_type' => PageType::NEWS->value,
        ]);

        $this->get('/newslist')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('NewsArchive')
                ->where('page.title', 'Архив новостей')
                ->has('news.data', 2)
                ->where('news.data.0.title', 'Новая новость')
                ->where('news.data.0.url', '/newslist/new.html')
                ->where('news.data.0.date', '05.02.2024')
                ->where('news.data.1.title', 'Старая новость')
                ->where('news.data.1.url', '/newslist/old.html')
                ->where('news.data.1.date', '10.01.2024'),
        );
    }

    public function test_news_archive_with_trailing_slash_is_accessible(): void
    {
        $this->get('/newslist/')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page->component('NewsArchive'),
        );
    }

    public function test_news_archive_returns_full_news_collection_for_frontend_pagination(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            CmsPage::factory()->create([
                'title' => 'Новость '.$i,
                'url' => '/newslist/news-'.$i.'.html',
                'page_status' => PageStatus::PUBLISHED->value,
                'page_of_type' => PageType::NEWS->value,
                'publication_date' => now()->subDays($i),
            ]);
        }

        $this->get('/newslist')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('NewsArchive')
                ->has('news.data', 12)
                ->where('news.meta.total', 12),
        );
    }

    public function test_news_archive_strips_legacy_pagination_from_archive_content(): void
    {
        CmsPage::factory()->create([
            'title' => 'Архив новостей',
            'url' => '/newslist',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'content' => '<p>Вступление</p><div class="pagination">Страницы: <a href="/newslist/?page=1">1</a> <a href="/newslist/?page=2">2</a></div>',
        ]);

        CmsPage::factory()->create([
            'title' => 'Новость 1',
            'url' => '/newslist/news-1.html',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::NEWS->value,
            'publication_date' => now(),
        ]);

        $this->get('/newslist')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('NewsArchive')
                ->where('page.content', fn (string $content): bool => str_contains($content, 'Вступление')
                    && ! str_contains($content, 'Страницы:')
                    && ! str_contains($content, 'newslist/?page=')),
        );
    }
}
