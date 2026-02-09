<?php

namespace Tests\Feature;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportKubanomsPageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_page_content_and_downloads_images(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/img/pic.jpg' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'http://kubanoms.ru/_files/doc.pdf' => Http::response('pdf-bytes', 200, ['Content-Type' => 'application/pdf']),
            'https://files.example.org/doc.docx' => Http::response('docx-bytes', 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
        ]);

        $menu = CmsMenu::query()->create([
            'name' => 'NAVBAR',
            'title' => 'Navbar',
            'max_depth' => 2,
        ]);

        CmsMenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Sample',
            'url' => '/sample.html',
            'sort_order' => 1,
            'visible' => true,
        ]);

        $html = <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="description" content="Описание страницы">
        <meta name="keywords" content="ключевые, слова">
        <title>Sample</title>
    </head>
    <body>
        <div class="middle_second">
            <div class="print">print</div>
            <div id="status">breadcrumbs</div>
            <div>
                <table class="nbm">
                    <tr>
                        <td class="pt0"><img src="img/spacer.gif" width="20" height="400" border="0"></td>
                        <td class="pt0" valign="top">
                            <h1>Sample Title</h1>
                            <p>Body <a href="page2.html">Next</a></p>
                            <p><a href="_files/doc.pdf">Doc</a></p>
                            <p><a href="https://files.example.org/doc.docx">External Doc</a></p>
                            <p><img src="img/pic.jpg"></p>
                            <video src="/_pictures/video.mp4"></video>
                            <form action="newslist/"></form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
HTML;

        $dir = storage_path('framework/testing/sitemap-content');
        File::ensureDirectoryExists($dir);
        File::put($dir.'/sample.html', $html);

        $this->artisan('kubanoms:import-page-content', [
            '--path' => $dir,
            '--base-url' => 'http://kubanoms.ru',
            '--image-dir' => 'cms/page/images',
            '--download-external-files' => true,
        ])->assertExitCode(0);

        $page = CmsPage::query()->where('url', '/sample.html')->first();
        $this->assertNotNull($page);
        $this->assertSame('Sample Title', $page->title);
        $this->assertSame('Описание страницы', $page->meta_description);
        $this->assertSame('ключевые, слова', $page->meta_keywords);
        $this->assertStringNotContainsString('<h1>', (string) $page->content);
        $this->assertStringContainsString('<a href="/page2.html">', (string) $page->content);
        $this->assertStringContainsString('href="/storage/cms/page/files/_files/doc.pdf"', (string) $page->content);
        $this->assertStringContainsString('href="/storage/cms/page/files/doc.docx"', (string) $page->content);
        $this->assertStringContainsString('src="/storage/cms/page/images/img/pic.jpg"', (string) $page->content);
        $this->assertStringContainsString('src="http://kubanoms.ru/_pictures/video.mp4"', (string) $page->content);
        $this->assertStringContainsString('action="/newslist/"', (string) $page->content);

        Storage::disk('public')->assertExists('cms/page/files/_files/doc.pdf');
        Storage::disk('public')->assertExists('cms/page/files/doc.docx');
        Storage::disk('public')->assertExists('cms/page/images/img/pic.jpg');
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/_files/doc.pdf']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/doc.docx']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/images/img/pic.jpg']);

        $file = CmsFile::query()->where('path', 'cms/page/files/_files/doc.pdf')->first();
        $this->assertNotNull($file);
        $this->assertSame('/storage/cms/page/files/_files/doc.pdf', $file->storage_url);

        $menuItem = CmsMenuItem::query()->where('menu_id', $menu->id)->first();
        $this->assertNotNull($menuItem);
        $this->assertSame($page->id, $menuItem->page_id);
        $this->assertNull($menuItem->url);

        File::deleteDirectory($dir);
    }

    public function test_it_can_import_only_documents_or_only_images(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/img/pic.jpg' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'http://kubanoms.ru/_files/doc.pdf' => Http::response('pdf-bytes', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $html = <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Sample</title>
    </head>
    <body>
        <div class="middle_second">
            <table class="nbm">
                <tr>
                    <td class="pt0" valign="top">
                        <h1>Sample Title</h1>
                        <p><a href="_files/doc.pdf">Doc</a></p>
                        <p><img src="img/pic.jpg"></p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
HTML;

        $documentsOnlyDir = storage_path('framework/testing/sitemap-content-documents-only');
        File::ensureDirectoryExists($documentsOnlyDir);
        File::put($documentsOnlyDir.'/sample.html', $html);

        $this->artisan('kubanoms:import-page-content', [
            '--path' => $documentsOnlyDir,
            '--base-url' => 'http://kubanoms.ru',
            '--without-images' => true,
        ])->assertExitCode(0);

        Storage::disk('public')->assertExists('cms/page/files/_files/doc.pdf');
        Storage::disk('public')->assertMissing('cms/page/images/img/pic.jpg');
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/_files/doc.pdf']);
        $this->assertDatabaseMissing('cms_file', ['path' => 'cms/page/images/img/pic.jpg']);

        /** @var CmsPage $documentsOnlyPage */
        $documentsOnlyPage = CmsPage::query()->where('url', '/sample.html')->firstOrFail();
        $this->assertStringContainsString('href="/storage/cms/page/files/_files/doc.pdf"', (string) $documentsOnlyPage->content);
        $this->assertStringContainsString('src="http://kubanoms.ru/img/pic.jpg"', (string) $documentsOnlyPage->content);

        CmsPage::query()->delete();
        CmsFile::query()->delete();
        Storage::disk('public')->deleteDirectory('cms/page/files');
        Storage::disk('public')->deleteDirectory('cms/page/images');

        $imagesOnlyDir = storage_path('framework/testing/sitemap-content-images-only');
        File::ensureDirectoryExists($imagesOnlyDir);
        File::put($imagesOnlyDir.'/sample.html', $html);

        $this->artisan('kubanoms:import-page-content', [
            '--path' => $imagesOnlyDir,
            '--base-url' => 'http://kubanoms.ru',
            '--without-documents' => true,
        ])->assertExitCode(0);

        Storage::disk('public')->assertMissing('cms/page/files/_files/doc.pdf');
        Storage::disk('public')->assertExists('cms/page/images/img/pic.jpg');
        $this->assertDatabaseMissing('cms_file', ['path' => 'cms/page/files/_files/doc.pdf']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/images/img/pic.jpg']);

        /** @var CmsPage $imagesOnlyPage */
        $imagesOnlyPage = CmsPage::query()->where('url', '/sample.html')->firstOrFail();
        $this->assertStringContainsString('href="/_files/doc.pdf"', (string) $imagesOnlyPage->content);
        $this->assertStringContainsString('src="/storage/cms/page/images/img/pic.jpg"', (string) $imagesOnlyPage->content);

        File::deleteDirectory($documentsOnlyDir);
        File::deleteDirectory($imagesOnlyDir);
    }
}
