<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use App\PageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsClearCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_only_news_pages(): void
    {
        CmsPage::factory()->count(3)->create([
            'page_of_type' => PageType::NEWS->value,
            'template' => 'news',
        ]);

        CmsPage::factory()->create([
            'title' => 'Архив новостей',
            'url' => '/newslist',
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
        ]);

        CmsPage::factory()->create([
            'title' => 'Обычная страница',
            'url' => '/about',
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
        ]);

        $this->artisan('kubanoms:news-clear')
            ->assertExitCode(0);

        $this->assertSame(0, CmsPage::query()->where('page_of_type', PageType::NEWS->value)->count());
        $this->assertSame(1, CmsPage::query()->where('url', '/newslist')->count());
        $this->assertSame(1, CmsPage::query()->where('url', '/about')->count());
    }

    public function test_it_supports_dry_run_without_data_changes(): void
    {
        CmsPage::factory()->count(2)->create([
            'page_of_type' => PageType::NEWS->value,
            'template' => 'news',
        ]);

        $before = CmsPage::query()->where('page_of_type', PageType::NEWS->value)->count();

        $this->artisan('kubanoms:news-clear', [
            '--dry-run' => true,
        ])->assertExitCode(0);

        $after = CmsPage::query()->where('page_of_type', PageType::NEWS->value)->count();
        $this->assertSame($before, $after);
    }
}
