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
}
