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

class RelinkKubanomsFileLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_kubanoms_files_and_replaces_links_in_page_content(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/_files/doc.pdf' => Http::response('pdf-bytes', 200, ['Content-Type' => 'application/pdf']),
            'http://kubanoms.ru/_files/doc2.docx?v=1' => Http::response(
                'docx-bytes',
                200,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'Test Page',
            'title_short' => 'Test Page',
            'content' => <<<'HTML'
<p><a href="http://kubanoms.ru/_files/doc.pdf">Doc</a></p>
<p><a href="/_files/doc2.docx?v=1">Doc2</a></p>
<p><a href="https://example.org/outside.pdf">Outside</a></p>
<p><a href="/news/demo.html">Internal Page</a></p>
HTML,
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
            'url' => '/test-page',
            'create_date' => now(),
            'create_user' => 'test',
            'update_date' => now(),
            'update_user' => 'test',
        ]);

        $this->artisan('kubanoms:relink-file-links', [
            '--base-url' => 'http://kubanoms.ru',
        ])->assertExitCode(0);

        $page->refresh();

        $this->assertStringContainsString('href="/storage/cms/page/files/_files/doc.pdf"', (string) $page->content);
        $this->assertStringContainsString('href="/storage/cms/page/files/_files/doc2__v_1.docx"', (string) $page->content);
        $this->assertStringContainsString('href="https://example.org/outside.pdf"', (string) $page->content);
        $this->assertStringContainsString('href="/news/demo.html"', (string) $page->content);

        Storage::disk('public')->assertExists('cms/page/files/_files/doc.pdf');
        Storage::disk('public')->assertExists('cms/page/files/_files/doc2__v_1.docx');

        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/_files/doc.pdf']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/_files/doc2__v_1.docx']);

        $file = CmsFile::query()->where('path', 'cms/page/files/_files/doc.pdf')->first();
        $this->assertNotNull($file);
        $this->assertSame('/storage/cms/page/files/_files/doc.pdf', $file->storage_url);
    }

    public function test_it_replaces_all_supported_file_types(): void
    {
        Storage::fake('public');

        /** @var array<int, array{file: string, content_type: string}> $fixtures */
        $fixtures = [
            ['file' => 'report.pdf', 'content_type' => 'application/pdf'],
            ['file' => 'letter.doc', 'content_type' => 'application/msword'],
            ['file' => 'letter.docx', 'content_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['file' => 'table.xls', 'content_type' => 'application/vnd.ms-excel'],
            ['file' => 'table.xlsx', 'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['file' => 'slides.ppt', 'content_type' => 'application/vnd.ms-powerpoint'],
            ['file' => 'slides.pptx', 'content_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            ['file' => 'notes.rtf', 'content_type' => 'application/rtf'],
            ['file' => 'readme.txt', 'content_type' => 'text/plain'],
            ['file' => 'archive.zip', 'content_type' => 'application/zip'],
            ['file' => 'archive.rar', 'content_type' => 'application/vnd.rar'],
            ['file' => 'video.mp4', 'content_type' => 'video/mp4'],
            ['file' => 'video.webm', 'content_type' => 'video/webm'],
            ['file' => 'audio.ogg', 'content_type' => 'audio/ogg'],
            ['file' => 'audio.mp3', 'content_type' => 'audio/mpeg'],
        ];

        $fakeResponses = [];
        $contentParts = [];

        foreach ($fixtures as $index => $fixture) {
            $url = 'http://kubanoms.ru/_files/'.$fixture['file'];
            $fakeResponses[$url] = Http::response(
                'binary-'.$fixture['file'],
                200,
                ['Content-Type' => $fixture['content_type']],
            );

            $href = match ($index % 3) {
                0 => $url,
                1 => '/_files/'.$fixture['file'],
                default => '//kubanoms.ru/_files/'.$fixture['file'],
            };

            $contentParts[] = '<p><a href="'.$href.'">'.$fixture['file'].'</a></p>';
        }

        Http::fake($fakeResponses);

        $page = CmsPage::query()->create([
            'title' => 'All Types Page',
            'title_short' => 'All Types Page',
            'content' => implode("\n", $contentParts),
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
            'url' => '/all-types-page',
            'create_date' => now(),
            'create_user' => 'test',
            'update_date' => now(),
            'update_user' => 'test',
        ]);

        $this->artisan('kubanoms:relink-file-links', [
            '--base-url' => 'http://kubanoms.ru',
            '--page-id' => [$page->id],
        ])->assertExitCode(0);

        $page->refresh();

        foreach ($fixtures as $fixture) {
            $storagePath = 'cms/page/files/_files/'.$fixture['file'];
            $storageUrl = '/storage/'.$storagePath;

            $this->assertStringContainsString('href="'.$storageUrl.'"', (string) $page->content);
            Storage::disk('public')->assertExists($storagePath);
            $this->assertDatabaseHas('cms_file', ['path' => $storagePath]);
        }

        $this->assertSame(count($fixtures), CmsFile::query()->count());
    }

    public function test_it_does_not_replace_link_when_server_returns_html_page_instead_of_file(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/_files/not-a-file.pdf' => Http::response(
                '<!doctype html><html><body>Not found</body></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
        ]);

        $page = CmsPage::query()->create([
            'title' => 'HTML Response Test',
            'title_short' => 'HTML Response Test',
            'content' => '<p><a href="http://kubanoms.ru/_files/not-a-file.pdf">Broken file</a></p>',
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
            'url' => '/html-response-test',
            'create_date' => now(),
            'create_user' => 'test',
            'update_date' => now(),
            'update_user' => 'test',
        ]);

        $this->artisan('kubanoms:relink-file-links', [
            '--base-url' => 'http://kubanoms.ru',
            '--page-id' => [$page->id],
        ])->assertExitCode(0);

        $page->refresh();

        $this->assertStringContainsString('href="http://kubanoms.ru/_files/not-a-file.pdf"', (string) $page->content);
        Storage::disk('public')->assertMissing('cms/page/files/_files/not-a-file.pdf');
        $this->assertDatabaseMissing('cms_file', ['path' => 'cms/page/files/_files/not-a-file.pdf']);
    }
}
