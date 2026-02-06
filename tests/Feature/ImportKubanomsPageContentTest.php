<?php

namespace Tests\Feature;

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
        ])->assertExitCode(0);

        $page = CmsPage::query()->where('url', '/sample.html')->first();
        $this->assertNotNull($page);
        $this->assertSame('Sample Title', $page->title);
        $this->assertSame('Описание страницы', $page->meta_description);
        $this->assertSame('ключевые, слова', $page->meta_keywords);
        $this->assertStringNotContainsString('<h1>', (string) $page->content);
        $this->assertStringContainsString('<a href="/page2.html">', (string) $page->content);
        $this->assertStringContainsString('http://kubanoms.ru/_files/doc.pdf', (string) $page->content);
        $this->assertStringContainsString('src="/storage/cms/page/images/img/pic.jpg"', (string) $page->content);
        $this->assertStringContainsString('src="http://kubanoms.ru/_pictures/video.mp4"', (string) $page->content);
        $this->assertStringContainsString('action="/newslist/"', (string) $page->content);

        Storage::disk('public')->assertExists('cms/page/images/img/pic.jpg');

        $menuItem = CmsMenuItem::query()->where('menu_id', $menu->id)->first();
        $this->assertNotNull($menuItem);
        $this->assertSame($page->id, $menuItem->page_id);
        $this->assertNull($menuItem->url);

        File::deleteDirectory($dir);
    }
}
