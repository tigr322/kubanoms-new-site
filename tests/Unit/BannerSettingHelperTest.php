<?php

namespace Tests\Unit;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use Tests\TestCase;

class BannerSettingHelperTest extends TestCase
{
    public function test_builds_html_from_json_image_banner(): void
    {
        config([
            'app.url' => 'http://example.test',
            'filesystems.disks.public.url' => 'http://example.test/storage',
        ]);

        $content = BannerSettingHelper::encodeContent([
            [
                'type' => 'image',
                'image' => 'cms/banners/image.png',
                'url' => 'https://example.com',
                'alt' => 'Баннер',
                'open_in_new_tab' => true,
            ],
        ]);

        $html = BannerSettingHelper::renderContent($content);

        $this->assertIsString($html);
        $this->assertStringContainsString('<a href="https://example.com"', $html);
        $this->assertStringContainsString('<img src="/storage/cms/banners/image.png"', $html);
        $this->assertStringContainsString('alt="Баннер"', $html);
    }

    public function test_renders_html_banner_snippet(): void
    {
        config([
            'app.url' => 'http://example.test',
            'filesystems.disks.public.url' => 'http://example.test/storage',
        ]);

        $content = BannerSettingHelper::encodeContent([
            [
                'type' => 'html',
                'html' => '<img src="/storage/cms/banners/image.png" alt="Баннер" />',
            ],
        ]);

        $html = BannerSettingHelper::renderContent($content);

        $this->assertSame('<img src="/storage/cms/banners/image.png" alt="Баннер" />', trim((string) $html));
    }

    public function test_decodes_html_content_to_banner_items(): void
    {
        $content = '<a href="https://example.com" target="_self"><img src="/storage/cms/banners/image.png" alt="Баннер" /></a>';

        $banners = BannerSettingHelper::decodeContent($content);

        $this->assertCount(1, $banners);
        $this->assertSame('image', $banners[0]['type']);
        $this->assertSame('cms/banners/image.png', $banners[0]['image']);
        $this->assertSame('https://example.com', $banners[0]['url']);
        $this->assertFalse($banners[0]['open_in_new_tab']);
    }
}
