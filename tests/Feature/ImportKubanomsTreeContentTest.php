<?php

namespace Tests\Feature;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportKubanomsTreeContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_content_tree_with_parent_relations_to_depth_three(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/a.html' => Http::response($this->pageA(), 200),
            'http://kubanoms.ru/a-1.html' => Http::response($this->pageA1(), 200),
            'http://kubanoms.ru/b2.html' => Http::response($this->pageB2(), 200),
            'http://kubanoms.ru/c3.html' => Http::response($this->pageC3(), 200),
            'http://kubanoms.ru/img/a-content.jpg' => Http::response('img-a', 200, ['Content-Type' => 'image/jpeg']),
            'http://kubanoms.ru/img/b-content.jpg' => Http::response('img-b', 200, ['Content-Type' => 'image/jpeg']),
            'http://kubanoms.ru/_files/doc.pdf' => Http::response('pdf-int-a', 200, ['Content-Type' => 'application/pdf']),
            'https://files.example.org/guides/a-guide.pdf' => Http::response('pdf-ext-a', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $sitemapPath = storage_path('framework/testing/kubanoms-sitemap-tree.html');
        File::put($sitemapPath, $this->sitemapHtml());

        $this->artisan('kubanoms:import-tree-content', [
            '--sitemap-file' => $sitemapPath,
            '--base-url' => 'http://kubanoms.ru',
            '--deep' => 3,
            '--image-dir' => 'cms/page/tree-images',
            '--download-external-files' => true,
        ])->assertExitCode(0);

        $pageA = CmsPage::query()->where('url', '/a.html')->first();
        $pageA1 = CmsPage::query()->where('url', '/a-1.html')->first();
        $pageB2 = CmsPage::query()->where('url', '/b2.html')->first();
        $pageC3 = CmsPage::query()->where('url', '/c3.html')->first();
        $pageD4 = CmsPage::query()->where('url', '/d4.html')->first();
        $outside = CmsPage::query()->where('url', '/outside.html')->first();

        $this->assertNotNull($pageA);
        $this->assertNotNull($pageA1);
        $this->assertNotNull($pageB2);
        $this->assertNotNull($pageC3);
        $this->assertNull($pageD4);
        $this->assertNull($outside);

        $this->assertNull($pageA->parent_id);
        $this->assertSame($pageA->id, $pageA1->parent_id);
        $this->assertSame($pageA->id, $pageB2->parent_id);
        $this->assertSame($pageB2->id, $pageC3->parent_id);

        $this->assertStringContainsString('<a href="/b2.html">', (string) $pageA->content);
        $this->assertStringNotContainsString('/outside.html', (string) $pageA->content);
        $this->assertStringContainsString('href="/storage/cms/page/files/guides/a-guide.pdf"', (string) $pageA->content);
        $this->assertStringContainsString('src="/storage/cms/page/tree-images/img/a-content.jpg"', (string) $pageA->content);
        $this->assertStringContainsString('src="/storage/cms/page/tree-images/img/b-content.jpg"', (string) $pageB2->content);

        Storage::disk('public')->assertExists('cms/page/files/guides/a-guide.pdf');
        Storage::disk('public')->assertExists('cms/page/tree-images/img/a-content.jpg');
        Storage::disk('public')->assertExists('cms/page/tree-images/img/b-content.jpg');
        Storage::disk('public')->assertMissing('cms/page/tree-images/img/outside.jpg');
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/files/guides/a-guide.pdf']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/tree-images/img/a-content.jpg']);
        $this->assertDatabaseHas('cms_file', ['path' => 'cms/page/tree-images/img/b-content.jpg']);

        $file = CmsFile::query()->where('path', 'cms/page/files/guides/a-guide.pdf')->first();
        $this->assertNotNull($file);
        $this->assertSame('/storage/cms/page/files/guides/a-guide.pdf', $file->storage_url);

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/outside.html'));
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/d4.html'));

        File::delete($sitemapPath);
    }

    private function sitemapHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <h1>Карта сайта</h1>
        <ul>
            <li>
                <a href="/a.html">Раздел A</a>
                <ul>
                    <li><a href="/a-1.html">Раздел A.1</a></li>
                </ul>
            </li>
        </ul>
    </body>
</html>
HTML;
    }

    private function pageA(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="description" content="desc a">
        <meta name="keywords" content="k1, k2">
    </head>
    <body>
        <div class="header">
            <a href="/outside.html">Outside link</a>
            <img src="/img/outside.jpg">
        </div>
        <div class="middle_second">
            <table class="nbm">
                <tr>
                    <td class="pt0" valign="top">
                        <h1>A title</h1>
                        <p>to b2 <a href="/b2.html">B2</a></p>
                        <p>external <a href="https://example.com/page">ext</a></p>
                        <p>external file <a href="https://files.example.org/guides/a-guide.pdf">guide</a></p>
                        <p><a href="/_files/doc.pdf">doc</a></p>
                        <p><img src="/img/a-content.jpg"></p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
HTML;
    }

    private function pageA1(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <div class="middle_second">
            <table class="nbm">
                <tr>
                    <td class="pt0" valign="top">
                        <h1>A1 title</h1>
                        <p>A1 body</p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
HTML;
    }

    private function pageB2(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <div class="middle_second">
            <table class="nbm">
                <tr>
                    <td class="pt0" valign="top">
                        <h1>B2 title</h1>
                        <p>to c3 <a href="/c3.html">C3</a></p>
                        <p><img src="/img/b-content.jpg"></p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
HTML;
    }

    private function pageC3(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <div class="middle_second">
            <table class="nbm">
                <tr>
                    <td class="pt0" valign="top">
                        <h1>C3 title</h1>
                        <p>to d4 <a href="/d4.html">D4</a></p>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
HTML;
    }
}
