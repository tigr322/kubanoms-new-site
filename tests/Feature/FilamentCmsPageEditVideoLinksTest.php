<?php

namespace Tests\Feature;

use App\Filament\Resources\Cms\CmsPages\Pages\EditCmsPage;
use App\Models\Cms\CmsPage;
use App\Models\User;
use App\PageStatus;
use App\PageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCmsPageEditVideoLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_shows_raw_html_field_with_video_links(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Видео',
            'title_short' => 'Видео',
            'content' => <<<'HTML'
<table><tr><td><video src="/storage/cms/page/videos/_pictures/video_new/petrovich.mp4" controls></video></td></tr></table>
HTML,
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
            'url' => '/page21913.html',
            'create_date' => now(),
            'create_user' => 'test',
            'update_date' => now(),
            'update_user' => 'test',
        ]);

        Livewire::actingAs($user)
            ->test(EditCmsPage::class, [
                'record' => $page->getRouteKey(),
            ])
            ->assertSet(
                'data.content_raw_html',
                '<table><tr><td><video src="/storage/cms/page/videos/_pictures/video_new/petrovich.mp4" controls></video></td></tr></table>',
            )
            ->assertSee('HTML контента (таблицы / video)')
            ->assertSee('/storage/cms/page/videos/_pictures/video_new/petrovich.mp4');
    }
}
