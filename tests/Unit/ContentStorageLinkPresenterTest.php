<?php

namespace Tests\Unit;

use App\Support\Cms\ContentStorageLinkPresenter;
use PHPUnit\Framework\TestCase;

class ContentStorageLinkPresenterTest extends TestCase
{
    public function test_collect_normalizes_and_deduplicates_storage_links(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $links = $presenter->collect(
            images: [
                'cms/news/images/photo.jpg',
                '/storage/cms/news/images/photo.jpg',
            ],
            attachments: [
                'public/cms/documents/attachments/report.pdf',
                'https://example.com/storage/cms/documents/attachments/report.pdf',
            ],
            content: '<p><a href="/storage/cms/files/guide.docx">Doc</a><img src="https://kubanoms.ru/storage/cms/news/images/photo.jpg" /></p>',
        );

        $this->assertSame([
            '/storage/cms/news/images/photo.jpg',
            '/storage/cms/documents/attachments/report.pdf',
            '/storage/cms/files/guide.docx',
        ], $links);
    }

    public function test_build_html_snippets_generates_img_and_anchor(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $snippets = $presenter->buildHtmlSnippets(
            images: ['/storage/cms/news/images/photo.jpg'],
            attachments: ['/storage/cms/documents/attachments/report.pdf'],
            content: null,
        );

        $this->assertStringContainsString('<img src="/storage/cms/news/images/photo.jpg" alt="" />', $snippets);
        $this->assertStringContainsString('<a href="/storage/cms/documents/attachments/report.pdf" target="_blank" rel="noopener">report.pdf</a>', $snippets);
    }

    public function test_build_plain_text_returns_hint_when_no_links(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $plain = $presenter->buildPlainText(
            images: [],
            attachments: [],
            content: '<p>without files</p>',
        );

        $this->assertStringContainsString('Ссылки появятся после загрузки файлов', $plain);
    }

    public function test_build_cms_files_table_html_contains_expected_cells(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $html = $presenter->buildCmsFilesTableHtml([
            [
                'id' => 15,
                'original_name' => 'contract.pdf',
                'path' => 'cms/files/contract.pdf',
                'storage_url' => '/storage/cms/files/contract.pdf',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'update_date' => '2026-02-11 10:20:30',
            ],
        ]);

        $this->assertStringContainsString('contract.pdf', $html);
        $this->assertStringContainsString('/storage/cms/files/contract.pdf', $html);
        $this->assertStringContainsString('application/pdf', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('Быстрый поиск', $html);
        $this->assertStringContainsString('data-search-index=', $html);
        $this->assertStringContainsString('data-copy-storage-url="/storage/cms/files/contract.pdf"', $html);
        $this->assertStringContainsString('Копировать', $html);
    }

    public function test_build_cms_files_table_html_returns_hint_for_empty_dataset(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $html = $presenter->buildCmsFilesTableHtml([]);

        $this->assertSame('Файлы не найдены.', $html);
    }

    public function test_collect_external_video_links_returns_only_external_mp4_urls(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $links = $presenter->collectExternalVideoLinks(<<<'HTML'
<p>
    <video src="http://kubanoms.ru/_pictures/video_new/first.mp4"></video>
    <a href="https://cdn.example.org/video/second.mp4?download=1">Second</a>
    <video src="/storage/cms/page/videos/local.mp4"></video>
    <a href="https://example.org/doc.pdf">Doc</a>
    <source src="//kubanoms.ru/_pictures/video_new/third.mp4" type="video/mp4">
</p>
HTML);

        $this->assertSame([
            'http://kubanoms.ru/_pictures/video_new/first.mp4',
            'https://cdn.example.org/video/second.mp4?download=1',
            '//kubanoms.ru/_pictures/video_new/third.mp4',
        ], $links);
    }

    public function test_build_external_video_plain_text_returns_hint_when_no_external_mp4_links(): void
    {
        $presenter = new ContentStorageLinkPresenter;

        $plain = $presenter->buildExternalVideoPlainText('<p><video src="/storage/cms/page/videos/local.mp4"></video></p>');

        $this->assertSame('Внешние mp4-ссылки не найдены.', $plain);
    }
}
