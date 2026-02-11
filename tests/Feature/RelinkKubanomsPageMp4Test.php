<?php

namespace Tests\Feature;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RelinkKubanomsPageMp4Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_relinks_mp4_links_for_single_page(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/videos/a.mp4' => Http::response('video-a', 200, ['Content-Type' => 'video/mp4']),
            'http://kubanoms.ru/media/b.mp4?v=2' => Http::response('video-b', 200, ['Content-Type' => 'video/mp4']),
            'http://kubanoms.ru/path/c.mp4' => Http::response('video-c', 200, ['Content-Type' => 'video/mp4']),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Видео страница',
            'title_short' => 'Видео страница',
            'content' => <<<'HTML'
<p><a href="http://kubanoms.ru/videos/a.mp4">Видео A</a></p>
<video controls src="/media/b.mp4?v=2"></video>
<video controls><source src="//kubanoms.ru/path/c.mp4" type="video/mp4"></video>
<p><a href="http://kubanoms.ru/files/doc.pdf">Документ</a></p>
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

        $this->artisan('kubanoms:relink-page-mp4', [
            '--page-url' => '/page21913.html',
            '--base-url' => 'http://kubanoms.ru',
        ])->assertExitCode(0);

        $page->refresh();

        $this->assertStringContainsString('href="/storage/cms/page/videos/videos/a.mp4"', (string) $page->content);
        $this->assertStringContainsString('src="/storage/cms/page/videos/media/b__v_2.mp4"', (string) $page->content);
        $this->assertStringContainsString('src="/storage/cms/page/videos/path/c.mp4"', (string) $page->content);
        $this->assertStringContainsString('href="http://kubanoms.ru/files/doc.pdf"', (string) $page->content);

        Storage::disk('public')->assertExists('cms/page/videos/videos/a.mp4');
        Storage::disk('public')->assertExists('cms/page/videos/media/b__v_2.mp4');
        Storage::disk('public')->assertExists('cms/page/videos/path/c.mp4');

        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/videos/videos/a.mp4']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/videos/media/b__v_2.mp4']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/videos/path/c.mp4']);

        $this->assertSame(3, CmsFile::query()->count());
        Http::assertSentCount(3);
    }

    public function test_it_returns_failure_when_page_not_found(): void
    {
        $this->artisan('kubanoms:relink-page-mp4', [
            '--page-url' => '/missing-page.html',
            '--base-url' => 'http://kubanoms.ru',
        ])->assertExitCode(1);
    }

    public function test_it_reuses_existing_storage_file_and_replaces_link_without_redownload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/page/videos/_pictures/video_new/already.mp4', 'existing-video');

        CmsFile::query()->create([
            'path' => 'cms/page/videos/_pictures/video_new/already.mp4',
            'original_name' => 'already.mp4',
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'description' => '',
            'create_date' => now(),
            'create_user' => 'test',
            'update_date' => now(),
            'update_user' => 'test',
        ]);

        Http::fake([
            '*' => Http::response('', 404),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Видео страница',
            'title_short' => 'Видео страница',
            'content' => <<<'HTML'
<video controls src="http://kubanoms.ru/_pictures/video_new/already.mp4"></video>
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

        $this->artisan('kubanoms:relink-page-mp4', [
            '--page-url' => '/page21913.html',
            '--base-url' => 'http://kubanoms.ru',
        ])->assertExitCode(0);

        $page->refresh();

        $this->assertStringContainsString(
            'src="/storage/cms/page/videos/_pictures/video_new/already.mp4"',
            (string) $page->content,
        );
        $this->assertStringNotContainsString('http://kubanoms.ru/_pictures/video_new/already.mp4', (string) $page->content);
        Http::assertNothingSent();
    }

    public function test_it_normalizes_dot_segments_in_mp4_links(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/../../x.mp4' => Http::response('video-x', 200, ['Content-Type' => 'video/mp4']),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Видео страница',
            'title_short' => 'Видео страница',
            'content' => <<<'HTML'
<video controls src="http://kubanoms.ru/../../x.mp4"></video>
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

        $this->artisan('kubanoms:relink-page-mp4', [
            '--page-url' => '/page21913.html',
            '--base-url' => 'http://kubanoms.ru',
        ])->assertExitCode(0);

        $page->refresh();

        $this->assertStringContainsString('src="/storage/cms/page/videos/x.mp4"', (string) $page->content);
        Storage::disk('public')->assertExists('cms/page/videos/x.mp4');
    }

    public function test_it_shows_failed_mp4_links_when_requested(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/videos/missing.mp4' => Http::response('', 404),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Видео страница',
            'title_short' => 'Видео страница',
            'content' => <<<'HTML'
<video controls src="http://kubanoms.ru/videos/missing.mp4"></video>
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

        $this->artisan('kubanoms:relink-page-mp4', [
            '--page-url' => '/page21913.html',
            '--base-url' => 'http://kubanoms.ru',
            '--show-failed' => true,
        ])
            ->expectsOutputToContain('Необработанные mp4 ссылки:')
            ->expectsOutputToContain('http://kubanoms.ru/videos/missing.mp4 | HTTP 404')
            ->assertExitCode(0);

        $page->refresh();
        $this->assertStringContainsString('http://kubanoms.ru/videos/missing.mp4', (string) $page->content);
    }
}
