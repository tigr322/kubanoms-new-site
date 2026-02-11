<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use App\Http\Requests\NewsIndexRequest;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use App\Services\PageResolverService;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function index(NewsIndexRequest $request): Response
    {
        $pageModel = $this->findArchivePage();
        $pageProps = $pageModel
            ? $this->pageResolverService->buildViewModel($pageModel)['page']
            : [
                'title' => 'Новости',
                'title_short' => 'Новости',
                'content' => '',
                'meta_description' => null,
                'meta_keywords' => null,
                'publication_date' => null,
                'page_status' => PageStatus::PUBLISHED,
                'page_of_type' => PageType::PAGE,
                'url' => '/newslist',
                'template' => 'default',
                'path' => null,
                'images' => [],
                'attachments' => [],
            ];

        $news = $this->newsQuery()
            ->get()
            ->map($this->transformListItem())
            ->values();

        return Inertia::render('NewsArchive', [
            'page' => [
                ...$pageProps,
                'content' => $this->normalizeArchiveContent((string) ($pageProps['content'] ?? '')),
            ],
            'news' => [
                'data' => $news->all(),
                'meta' => [
                    'total' => $news->count(),
                ],
            ],
            ...$this->pageResolverService->layout(),
            'special' => (int) $request->cookie('special', '0'),
        ]);
    }

    private function newsQuery(): Builder
    {
        return CmsPage::query()
            ->where('page_of_type', PageType::NEWS->value)
            ->where('page_status', PageStatus::PUBLISHED->value)
            ->orderByDesc('publication_date')
            ->orderByDesc('id');
    }

    private function findArchivePage(): ?CmsPage
    {
        return CmsPage::query()
            ->whereIn('url', ['/newslist', '/newslist/'])
            ->first();
    }

    private function transformListItem(): callable
    {
        return static fn (CmsPage $item): array => [
            'id' => $item->id,
            'title' => $item->title,
            'url' => $item->url,
            'date' => optional($item->publication_date)?->format('d.m.Y'),
            'image' => self::resolvePreviewImage(
                collect($item->images ?? [])->filter()->first(),
                is_string($item->content) ? $item->content : null,
            ),
        ];
    }

    private static function normalizeMediaPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $clean = $path;

        if (preg_match('#https?://[^/]+(/storage/.*)$#', $path, $matches)) {
            $clean = $matches[1];
        }

        if (preg_match('#^https?://#i', $clean)) {
            return null;
        }

        if (Str::startsWith($clean, '//')) {
            return '/'.ltrim($clean, '/');
        }

        if (Str::startsWith($clean, '/storage/')) {
            return $clean;
        }

        $normalized = preg_replace('#^public/#', '', ltrim($clean, '/'));

        return '/storage/'.$normalized;
    }

    private static function resolvePreviewImage(?string $image, ?string $content): ?string
    {
        $normalizedImage = self::normalizeMediaPath($image);

        if ($normalizedImage) {
            return $normalizedImage;
        }

        return self::extractFirstContentImage($content);
    }

    private static function extractFirstContentImage(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        if (! preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            return null;
        }

        $sources = $matches[1] ?? [];

        foreach ($sources as $source) {
            if (! is_string($source) || ! self::isStorageCompatibleImageSource($source)) {
                continue;
            }

            $normalized = self::normalizeMediaPath($source);

            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private static function isStorageCompatibleImageSource(string $source): bool
    {
        return Str::startsWith($source, ['/storage/', 'storage/', 'cms/'])
            || (bool) preg_match('#https?://[^/]+/storage/#i', $source);
    }

    private function normalizeArchiveContent(string $content): string
    {
        $normalized = BannerSettingHelper::normalizeContent($content) ?? '';

        if ($normalized === '') {
            return '';
        }

        return $this->stripLegacyNewsMarkup($normalized);
    }

    private function stripLegacyNewsMarkup(string $content): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="news-archive-content">'.$content.'</div>');
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $wrapper = $xpath->query('//*[@id="news-archive-content"]')->item(0);

        if (! $wrapper instanceof DOMElement) {
            return $content;
        }

        $selectors = [
            './/*[contains(concat(" ", normalize-space(@class), " "), " news ")]',
            './/*[contains(concat(" ", normalize-space(@class), " "), " pagination ")]',
            './/*[contains(concat(" ", normalize-space(@class), " "), " pagen ")]',
            './/p[contains(normalize-space(.), "Страницы:")]',
            './/div[contains(normalize-space(.), "Страницы:")]',
            './/a[contains(@href, "newslist/?page=")]/ancestor::*[self::p or self::div or self::td][1]',
            './/a[contains(@href, "PAGEN_1=")]/ancestor::*[self::p or self::div or self::td][1]',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $wrapper);

            if (! $nodes) {
                continue;
            }

            $toRemove = [];

            foreach ($nodes as $node) {
                $toRemove[] = $node;
            }

            foreach ($toRemove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        return trim($this->innerHtml($wrapper));
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }
}
