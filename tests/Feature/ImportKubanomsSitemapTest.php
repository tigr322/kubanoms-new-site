<?php

namespace Tests\Feature;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportKubanomsSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_pages_and_menu_items_from_sitemap(): void
    {
        $sidebarMenu = CmsMenu::query()->create([
            'name' => 'SIDEBAR',
            'title' => 'Sidebar',
            'max_depth' => 3,
        ]);

        $navbarMenu = CmsMenu::query()->create([
            'name' => 'NAVBAR',
            'title' => 'Navbar',
            'max_depth' => 2,
        ]);

        CmsPage::query()->create([
            'url' => '/old.html',
            'title' => 'Old',
            'page_status' => 3,
            'page_of_type' => 1,
            'template' => 'default',
        ]);

        $html = <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Карта сайта</title>
    </head>
    <body>
        <h1>Карта сайта</h1>
        <ul>
            <li>
                <a href="grazhd.html">Гражданам</a>
                <ul>
                    <li><a href="grazhd_01.html">Памятки</a></li>
                </ul>
            </li>
            <li><a href="contact.html">Контакты</a></li>
        </ul>
    </body>
</html>
HTML;

        $path = storage_path('framework/testing/sitemap.html');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);

        $this->artisan('kubanoms:import-sitemap', [
            '--file' => $path,
            '--base-url' => 'http://kubanoms.ru',
            '--menu' => 'SIDEBAR',
            '--wipe' => true,
            '--truncate' => true,
            '--navbar' => true,
            '--navbar-titles' => 'Гражданам',
        ])->assertExitCode(0);

        $urls = CmsPage::query()->orderBy('url')->pluck('url')->all();
        $this->assertEqualsCanonicalizing([
            '/contact.html',
            '/grazhd.html',
            '/grazhd_01.html',
        ], $urls);

        $parentPage = CmsPage::query()->where('url', '/grazhd.html')->first();
        $childPage = CmsPage::query()->where('url', '/grazhd_01.html')->first();
        $contactPage = CmsPage::query()->where('url', '/contact.html')->first();

        $this->assertNotNull($parentPage);
        $this->assertNotNull($childPage);
        $this->assertNotNull($contactPage);
        $this->assertSame($parentPage->id, $childPage->parent_id);

        $this->assertSame(3, CmsMenuItem::query()->where('menu_id', $sidebarMenu->id)->count());
        $this->assertSame(2, CmsMenuItem::query()->where('menu_id', $navbarMenu->id)->count());

        File::delete($path);
    }

    public function test_it_keeps_utf8_when_meta_declares_windows1251(): void
    {
        CmsMenu::query()->create([
            'name' => 'SIDEBAR',
            'title' => 'Sidebar',
            'max_depth' => 3,
        ]);

        $html = <<<'HTML'
<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
        <title>Карта сайта</title>
    </head>
    <body>
        <h1>Карта сайта</h1>
        <ul>
            <li><a href="grazhd.html">Гражданам</a></li>
        </ul>
    </body>
</html>
HTML;

        $path = storage_path('framework/testing/sitemap-win1251.html');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);

        $this->artisan('kubanoms:import-sitemap', [
            '--file' => $path,
            '--base-url' => 'http://kubanoms.ru',
            '--menu' => 'SIDEBAR',
            '--truncate' => true,
        ])->assertExitCode(0);

        $page = CmsPage::query()->where('url', '/grazhd.html')->first();
        $this->assertNotNull($page);
        $this->assertSame('Гражданам', $page->title);

        File::delete($path);
    }
}
