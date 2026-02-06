<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use App\PageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportKubanomsNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_news_from_list_pages(): void
    {
        Storage::fake('public');
        Http::fake([
            'http://kubanoms.ru/newslist/?page=1' => Http::response($this->listHtml(), 200),
            'http://kubanoms.ru/newslist/item-1.html' => Http::response($this->detailHtmlOne(), 200),
            'http://kubanoms.ru/newslist/item-2.html' => Http::response($this->detailHtmlTwo(), 200),
            'http://kubanoms.ru/img/news1.jpg' => Http::response('image-1', 200, ['Content-Type' => 'image/jpeg']),
            'http://kubanoms.ru/_events/preview2.jpg' => Http::response('image-2', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->artisan('kubanoms:import-news', [
            '--start' => 1,
            '--end' => 1,
            '--base-url' => 'http://kubanoms.ru',
            '--image-dir' => 'cms/news/images',
        ])->assertExitCode(0);

        $first = CmsPage::query()->where('url', '/newslist/item-1.html')->first();
        $this->assertNotNull($first);
        $this->assertSame(PageType::NEWS, $first->page_of_type);
        $this->assertSame('news', $first->template);
        $this->assertSame('05.02.2026', $first->publication_date?->format('d.m.Y'));
        $this->assertStringContainsString('<a href="/page2.html">', (string) $first->content);
        $this->assertStringContainsString('http://kubanoms.ru/_files/doc.pdf', (string) $first->content);
        $this->assertStringNotContainsString('class="print"', (string) $first->content);
        $this->assertContains('/storage/cms/news/images/img/news1.jpg', $first->images ?? []);

        $second = CmsPage::query()->where('url', '/newslist/item-2.html')->first();
        $this->assertNotNull($second);
        $this->assertSame('04.02.2026', $second->publication_date?->format('d.m.Y'));
        $this->assertContains('/storage/cms/news/images/_events/preview2.jpg', $second->images ?? []);

        Storage::disk('public')->assertExists('cms/news/images/img/news1.jpg');
        Storage::disk('public')->assertExists('cms/news/images/_events/preview2.jpg');
    }

    private function listHtml(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <div class="news">
            <img src="_events/preview2.jpg" width="158" height="106" alt="" align="left" class="imgbrd">
            <div class="date">04.02.2026</div>
            <h3 class="titlenews"><a href="newslist/item-2.html">Second</a></h3>
            <p class="link"><a href="newslist/item-2.html">Посмотреть полностью</a></p>
        </div>
        <div class="news">
            <div class="date">05.02.2026</div>
            <h3 class="titlenews"><a href="newslist/item-1.html">First</a></h3>
            <p class="link"><a href="newslist/item-1.html">Посмотреть полностью</a></p>
        </div>
    </body>
</html>
HTML;
    }

    private function detailHtmlOne(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="description" content="Описание 1">
        <meta name="keywords" content="ключ, слово">
    </head>
    <body>
        <div class="middle_second">
            <div class="print">print</div>
            <div id="status">breadcrumbs</div>
            <div>
                <table class="nbm">
                    <tr>
                        <td class="pt0" valign="top">
                            <h1>First Title</h1>
                            <div class="date">05.02.2026</div>
                            <p>Body <a href="page2.html">Next</a></p>
                            <p><a href="_files/doc.pdf">Doc</a></p>
                            <p><img src="img/news1.jpg"></p>
                            <form action="newslist/"></form>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
HTML;
    }

    private function detailHtmlTwo(): string
    {
        return <<<'HTML'
<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <div class="middle_second">
            <div>
                <table class="nbm">
                    <tr>
                        <td class="pt0" valign="top">
                            <h1>Second Title</h1>
                            <div class="date">04.02.2026</div>
                            <p>Second body</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>
HTML;
    }
}
