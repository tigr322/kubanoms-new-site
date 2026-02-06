<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DownloadKubanomsSitemapPagesTest extends TestCase
{
    public function test_it_downloads_internal_html_links_from_sitemap_file(): void
    {
        Http::fake([
            'http://kubanoms.ru/grazhd.html' => Http::response('<html>grazhd</html>', 200),
            'http://kubanoms.ru/sitemap/?template=print' => Http::response('<html>print</html>', 200),
            'http://kubanoms.ru/docs/price.pdf' => Http::response('%PDF', 200),
            'http://example.com/external.html' => Http::response('<html>external</html>', 200),
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
            <li><a href="grazhd.html">Гражданам</a></li>
            <li><a href="sitemap/?template=print">Карта сайта</a></li>
            <li><a href="docs/price.pdf">Документ</a></li>
            <li><a href="http://example.com/external.html">External</a></li>
        </ul>
    </body>
</html>
HTML;

        $path = storage_path('framework/testing/sitemap-download.html');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);

        $outputDir = storage_path('framework/testing/sitemap-downloads/default');
        File::deleteDirectory($outputDir);

        $this->artisan('kubanoms:download-sitemap-pages', [
            '--file' => $path,
            '--base-url' => 'http://kubanoms.ru',
            '--output' => $outputDir,
        ])->assertExitCode(0);

        $this->assertFileExists($outputDir.'/grazhd.html');
        $this->assertFileExists($outputDir.'/sitemap/index__template_print.html');
        $this->assertFileDoesNotExist($outputDir.'/docs/price.pdf');
        $this->assertFileDoesNotExist($outputDir.'/example.com/external.html');

        Http::assertSentCount(2);

        File::delete($path);
        File::deleteDirectory($outputDir);
    }

    public function test_it_can_include_external_and_file_links(): void
    {
        Http::fake([
            'http://kubanoms.ru/grazhd.html' => Http::response('<html>grazhd</html>', 200),
            'http://kubanoms.ru/docs/price.pdf' => Http::response('%PDF', 200),
            'http://example.com/external.html' => Http::response('<html>external</html>', 200),
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
            <li><a href="grazhd.html">Гражданам</a></li>
            <li><a href="docs/price.pdf">Документ</a></li>
            <li><a href="http://example.com/external.html">External</a></li>
        </ul>
    </body>
</html>
HTML;

        $path = storage_path('framework/testing/sitemap-download-extended.html');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);

        $outputDir = storage_path('framework/testing/sitemap-downloads/extended');
        File::deleteDirectory($outputDir);

        $this->artisan('kubanoms:download-sitemap-pages', [
            '--file' => $path,
            '--base-url' => 'http://kubanoms.ru',
            '--output' => $outputDir,
            '--include-files' => true,
            '--include-external' => true,
        ])->assertExitCode(0);

        $this->assertFileExists($outputDir.'/grazhd.html');
        $this->assertFileExists($outputDir.'/docs/price.pdf');
        $this->assertFileExists($outputDir.'/example.com/external.html');

        Http::assertSentCount(3);

        File::delete($path);
        File::deleteDirectory($outputDir);
    }

    public function test_it_returns_failure_when_downloads_fail(): void
    {
        Http::fake([
            'http://kubanoms.ru/grazhd.html' => Http::response('Not found', 404),
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
            <li><a href="grazhd.html">Гражданам</a></li>
        </ul>
    </body>
</html>
HTML;

        $path = storage_path('framework/testing/sitemap-download-fail.html');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);

        $outputDir = storage_path('framework/testing/sitemap-downloads/failure');
        File::deleteDirectory($outputDir);

        $this->artisan('kubanoms:download-sitemap-pages', [
            '--file' => $path,
            '--base-url' => 'http://kubanoms.ru',
            '--output' => $outputDir,
        ])->assertExitCode(1);

        $this->assertFileDoesNotExist($outputDir.'/grazhd.html');

        Http::assertSentCount(3);

        File::delete($path);
        File::deleteDirectory($outputDir);
    }
}
