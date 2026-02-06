<?php

namespace App\Services\Import;

use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KubanomsPageContentImporter
{
    /**
     * @var array<int, string>
     */
    private const array FILE_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'rtf',
        'txt',
        'zip',
        'rar',
        'mp4',
        'webm',
        'ogg',
        'mp3',
    ];

    private const string SYSTEM_USER = 'import:kubanoms';

    /**
     * @return array{
     *     files_total: int,
     *     pages_created: int,
     *     pages_updated: int,
     *     menu_items_updated: int,
     *     content_missing: int,
     *     images_downloaded: int,
     *     images_failed: int,
     *     images_skipped: int
     * }
     */
    public function importFromDirectory(
        string $directory,
        string $baseUrl,
        string $disk = 'public',
        string $imageDirectory = 'cms/page/images',
        bool $updateExistingMeta = false,
        ?int $limit = null,
    ): array {
        $resolved = $this->resolveFilePath($directory);

        if (! is_dir($resolved)) {
            throw new RuntimeException(sprintf('Папка не найдена: %s', $resolved));
        }

        $files = array_values(array_filter(
            File::allFiles($resolved),
            fn ($file): bool => in_array(strtolower($file->getExtension()), ['html', 'htm'], true),
        ));

        $stats = [
            'files_total' => count($files),
            'pages_created' => 0,
            'pages_updated' => 0,
            'menu_items_updated' => 0,
            'content_missing' => 0,
            'images_downloaded' => 0,
            'images_failed' => 0,
            'images_skipped' => 0,
        ];

        $baseRoot = $this->baseRoot($baseUrl);
        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';
        $imageDirectory = trim($imageDirectory, '/');
        $imageCache = [];
        $processed = 0;

        foreach ($files as $file) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $relativePath = $this->relativePath($resolved, $file->getPathname());
            $pageUrl = $this->pageUrlFromPath($relativePath);

            if ($pageUrl === null) {
                continue;
            }

            $html = File::get($file->getPathname());

            if ($html === false) {
                continue;
            }

            $normalizedHtml = $this->normalizeHtml($html);
            $parsed = $this->parseContent(
                html: $normalizedHtml,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                imageDirectory: $imageDirectory,
                imageCache: $imageCache,
                stats: $stats,
            );

            if (! $parsed) {
                $stats['content_missing']++;

                continue;
            }

            $page = CmsPage::query()->where('url', $pageUrl)->first();
            $title = $parsed['title'] ?: ($page?->title ?? $this->titleFromPath($relativePath));
            $metaDescription = $parsed['meta_description'];
            $metaKeywords = $parsed['meta_keywords'];

            if (! $page) {
                $page = CmsPage::query()->create([
                    'parent_id' => null,
                    'title' => $title,
                    'title_short' => $title,
                    'content' => $parsed['content'],
                    'page_status' => PageStatus::PUBLISHED->value,
                    'page_of_type' => PageType::PAGE->value,
                    'template' => 'default',
                    'url' => $pageUrl,
                    'meta_description' => $metaDescription,
                    'meta_keywords' => $metaKeywords,
                    'create_date' => now(),
                    'create_user' => self::SYSTEM_USER,
                    'update_date' => now(),
                    'update_user' => self::SYSTEM_USER,
                ]);

                $stats['pages_created']++;
            } else {
                $updates = [
                    'content' => $parsed['content'],
                    'update_date' => now(),
                    'update_user' => self::SYSTEM_USER,
                ];

                if ($updateExistingMeta || $page->title === null || $page->title === '') {
                    $updates['title'] = $title;
                    $updates['title_short'] = $title;
                }

                if ($updateExistingMeta || ! $page->meta_description) {
                    $updates['meta_description'] = $metaDescription;
                }

                if ($updateExistingMeta || ! $page->meta_keywords) {
                    $updates['meta_keywords'] = $metaKeywords;
                }

                $page->update($updates);
                $stats['pages_updated']++;
            }

            $stats['menu_items_updated'] += $this->attachMenuItems($page, $baseRoot);
            $processed++;
        }

        return $stats;
    }

    /**
     * @return array{title: string|null, content: string, meta_description: string|null, meta_keywords: string|null}|null
     */
    private function parseContent(
        string $html,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $imageDirectory,
        array &$imageCache,
        array &$stats,
    ): ?array {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $prepared = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
        $dom->loadHTML($prepared, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $contentNode = $this->findContentNode($xpath);

        if (! $contentNode) {
            return null;
        }

        $title = $this->extractTitle($contentNode);
        $metaDescription = $this->extractMeta($xpath, 'description');
        $metaKeywords = $this->extractMeta($xpath, 'keywords');

        $this->removeNodesByTag($contentNode, ['script', 'style']);
        $this->removeNodesBySelector($xpath, './/div[contains(@class,"print")]', $contentNode);
        $this->removeNodesBySelector($xpath, './/div[@id="status"]', $contentNode);

        $this->normalizeLinks($contentNode, $baseRoot, $baseHost);
        $this->normalizeForms($contentNode, $baseRoot, $baseHost);
        $this->normalizeMediaSources($contentNode, $baseRoot);
        $this->normalizeImages(
            contentNode: $contentNode,
            baseRoot: $baseRoot,
            baseHost: $baseHost,
            disk: $disk,
            imageDirectory: $imageDirectory,
            imageCache: $imageCache,
            stats: $stats,
        );

        return [
            'title' => $title,
            'content' => trim($this->innerHtml($contentNode)),
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
        ];
    }

    private function findContentNode(DOMXPath $xpath): ?DOMElement
    {
        $nodes = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " middle_second ")]//h1[1]/ancestor::td[1]',
        );

        if ($nodes && $nodes->length > 0 && $nodes->item(0) instanceof DOMElement) {
            return $nodes->item(0);
        }

        $nodes = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " middle_second ")]//table[1]//tr[1]/td[@valign="top"][1]',
        );

        if ($nodes && $nodes->length > 0 && $nodes->item(0) instanceof DOMElement) {
            return $nodes->item(0);
        }

        $nodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " middle_second ")]');

        if ($nodes && $nodes->length > 0 && $nodes->item(0) instanceof DOMElement) {
            return $nodes->item(0);
        }

        return null;
    }

    private function extractTitle(DOMElement $contentNode): ?string
    {
        $h1 = $contentNode->getElementsByTagName('h1')->item(0);

        if (! $h1 instanceof DOMElement) {
            return null;
        }

        $title = trim(preg_replace('/\s+/u', ' ', $h1->textContent ?? '') ?? '');

        if ($h1->parentNode) {
            $h1->parentNode->removeChild($h1);
        }

        return $title !== '' ? $title : null;
    }

    private function extractMeta(DOMXPath $xpath, string $name): ?string
    {
        $nodes = $xpath->query(sprintf('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="%s"]', strtolower($name)));

        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $content = trim((string) $node->getAttribute('content'));

        return $content !== '' ? $content : null;
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function removeNodesByTag(DOMElement $contentNode, array $tags): void
    {
        foreach ($tags as $tag) {
            $nodes = $contentNode->getElementsByTagName($tag);

            foreach ($this->toArray($nodes) as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    private function removeNodesBySelector(DOMXPath $xpath, string $selector, DOMElement $context): void
    {
        $nodes = $xpath->query($selector, $context);

        if (! $nodes) {
            return;
        }

        foreach ($this->toArray($nodes) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    private function normalizeLinks(DOMElement $contentNode, string $baseRoot, string $baseHost): void
    {
        foreach ($this->toArray($contentNode->getElementsByTagName('a')) as $link) {
            $href = $link->getAttribute('href');

            if ($href === '') {
                continue;
            }

            $normalized = $this->normalizeHref($href, $baseRoot, $baseHost);

            if ($normalized !== '') {
                $link->setAttribute('href', $normalized);
            }
        }
    }

    private function normalizeForms(DOMElement $contentNode, string $baseRoot, string $baseHost): void
    {
        foreach ($this->toArray($contentNode->getElementsByTagName('form')) as $form) {
            $action = $form->getAttribute('action');

            if ($action === '') {
                continue;
            }

            $normalized = $this->normalizeHref($action, $baseRoot, $baseHost);

            if ($normalized !== '') {
                $form->setAttribute('action', $normalized);
            }
        }
    }

    private function normalizeMediaSources(DOMElement $contentNode, string $baseRoot): void
    {
        foreach (['video', 'audio', 'source'] as $tag) {
            foreach ($this->toArray($contentNode->getElementsByTagName($tag)) as $node) {
                $src = $node->getAttribute('src');

                if ($src === '') {
                    continue;
                }

                $normalized = $this->normalizeExternalSrc($src, $baseRoot);

                if ($normalized !== '') {
                    $node->setAttribute('src', $normalized);
                }
            }
        }
    }

    private function normalizeImages(
        DOMElement $contentNode,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $imageDirectory,
        array &$imageCache,
        array &$stats,
    ): void {
        foreach ($this->toArray($contentNode->getElementsByTagName('img')) as $image) {
            $src = $image->getAttribute('src');

            if ($src === '') {
                continue;
            }

            $normalized = $this->downloadImage(
                src: $src,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                imageDirectory: $imageDirectory,
                imageCache: $imageCache,
                stats: $stats,
            );

            if ($normalized !== '') {
                $image->setAttribute('src', $normalized);
            }
        }
    }

    private function normalizeHref(string $href, string $baseRoot, string $baseHost): string
    {
        $href = trim($href);

        if ($href === '' || Str::startsWith($href, ['#', 'mailto:', 'tel:', 'javascript:'])) {
            return $href;
        }

        $absolute = $this->absoluteUrl($href, $baseRoot);

        if ($absolute === '') {
            return $href;
        }

        $path = parse_url($absolute, PHP_URL_PATH) ?? '';
        $query = parse_url($absolute, PHP_URL_QUERY);

        if ($this->isInternalUrl($absolute, $baseHost)) {
            if ($this->isFileLink($path)) {
                return $absolute;
            }

            $normalized = '/'.ltrim($path, '/');

            if ($query) {
                $normalized .= '?'.$query;
            }

            return $normalized;
        }

        return $absolute;
    }

    private function normalizeExternalSrc(string $src, string $baseRoot): string
    {
        $src = trim($src);

        if ($src === '' || Str::startsWith($src, ['data:', 'blob:'])) {
            return $src;
        }

        return $this->absoluteUrl($src, $baseRoot);
    }

    private function downloadImage(
        string $src,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $imageDirectory,
        array &$imageCache,
        array &$stats,
    ): string {
        $src = trim($src);

        if ($src === '' || Str::startsWith($src, ['data:', 'blob:'])) {
            $stats['images_skipped']++;

            return $src;
        }

        $absolute = $this->absoluteUrl($src, $baseRoot);

        if ($absolute === '') {
            $stats['images_skipped']++;

            return $src;
        }

        if (! $this->isInternalUrl($absolute, $baseHost)) {
            $stats['images_skipped']++;

            return $absolute;
        }

        if (isset($imageCache[$absolute])) {
            return $imageCache[$absolute];
        }

        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsContentImporter/1.0)')
                ->get($absolute);

            if ($response->failed()) {
                $stats['images_failed']++;

                return $absolute;
            }

            $contentType = (string) ($response->header('Content-Type') ?? '');
            $targetPath = $this->imageTargetPath($imageDirectory, $absolute, $contentType);
            Storage::disk($disk)->put($targetPath, $response->body());
            $url = Storage::disk($disk)->url($targetPath);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;

            $imageCache[$absolute] = $urlPath;
            $stats['images_downloaded']++;

            return $urlPath;
        } catch (Throwable $exception) {
            $stats['images_failed']++;

            return $absolute;
        }
    }

    private function imageTargetPath(string $imageDirectory, string $absoluteUrl, string $contentType): string
    {
        $path = parse_url($absoluteUrl, PHP_URL_PATH) ?? '';
        $path = ltrim($path, '/');
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        if ($path === '') {
            $path = 'image';
        }

        $path = $this->appendQuerySuffix($path, (string) (parse_url($absoluteUrl, PHP_URL_QUERY) ?? ''));

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === '') {
            $extension = $this->extensionFromContentType($contentType);
            $path .= '.'.$extension;
        }

        return trim($imageDirectory, '/').'/'.$path;
    }

    private function extensionFromContentType(string $contentType): string
    {
        $type = strtolower(trim(strtok($contentType, ';') ?: ''));

        return match ($type) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'bin',
        };
    }

    private function appendQuerySuffix(string $path, string $query): string
    {
        if ($query === '') {
            return $path;
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $query) ?? '';

        if ($safe === '') {
            return $path;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $targetFilename = $filename.'__'.$safe;

        if ($extension !== '') {
            $targetFilename .= '.'.$extension;
        }

        if ($directory === '' || $directory === '.') {
            return $targetFilename;
        }

        return $directory.'/'.$targetFilename;
    }

    private function absoluteUrl(string $url, string $baseRoot): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            $scheme = parse_url($baseRoot, PHP_URL_SCHEME) ?? 'http';

            return $scheme.':'.$url;
        }

        if (Str::startsWith($url, ['mailto:', 'tel:', 'data:', 'blob:'])) {
            return $url;
        }

        if (Str::startsWith($url, '/')) {
            return rtrim($baseRoot, '/').$url;
        }

        return rtrim($baseRoot, '/').'/'.ltrim($url, '/');
    }

    private function isInternalUrl(string $url, string $baseHost): bool
    {
        if (Str::startsWith($url, ['mailto:', 'tel:'])) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return true;
        }

        return strtolower($host) === strtolower($baseHost);
    }

    private function isFileLink(string $path): bool
    {
        if (Str::startsWith($path, ['mailto:', 'tel:'])) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' && in_array($extension, self::FILE_EXTENSIONS, true);
    }

    /**
     * @return array<int, \DOMNode>
     */
    private function toArray(DOMNodeList $nodes): array
    {
        $items = [];

        foreach ($nodes as $node) {
            $items[] = $node;
        }

        return $items;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }

    private function baseRoot(string $url): string
    {
        $parts = parse_url($url);

        if (! $parts || ! isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('Невозможно определить базовый URL.');
        }

        return $parts['scheme'].'://'.$parts['host'];
    }

    private function resolveFilePath(string $filePath): string
    {
        $path = trim($filePath);

        if ($path === '') {
            return $path;
        }

        if (Str::startsWith($path, ['/']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function relativePath(string $root, string $path): string
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $relative = str_replace($root, '', $path);

        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    private function pageUrlFromPath(string $relativePath): ?string
    {
        $normalized = ltrim($relativePath, '/');

        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'index.html' || $normalized === 'index.htm') {
            return '/';
        }

        if (str_ends_with($normalized, '/index.html') || str_ends_with($normalized, '/index.htm')) {
            $dir = rtrim(dirname($normalized), '.');

            return '/'.trim($dir, '/').'/';
        }

        return '/'.ltrim($normalized, '/');
    }

    private function titleFromPath(string $relativePath): string
    {
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);

        if ($filename === '' || $filename === 'index') {
            return 'Без названия';
        }

        return $filename;
    }

    private function normalizeHtml(string $html): string
    {
        $encoding = $this->detectEncoding($html);

        if ($encoding) {
            $upper = strtoupper($encoding);

            if ($upper !== 'UTF-8') {
                $detected = mb_detect_encoding($html, ['UTF-8', $upper], true);

                if ($detected && strtoupper($detected) === 'UTF-8') {
                    return $html;
                }

                return mb_convert_encoding($html, 'UTF-8', $encoding);
            }

            return $html;
        }

        if (mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        $detected = mb_detect_encoding($html, ['Windows-1251', 'KOI8-R', 'ISO-8859-1'], true);

        if ($detected && strtoupper($detected) !== 'UTF-8') {
            return mb_convert_encoding($html, 'UTF-8', $detected);
        }

        return $html;
    }

    private function detectEncoding(string $html): ?string
    {
        if (preg_match('/charset=([a-zA-Z0-9\\-]+)/i', $html, $matches)) {
            return $matches[1];
        }

        $detected = mb_detect_encoding($html, ['UTF-8', 'Windows-1251', 'KOI8-R', 'ISO-8859-1'], true);

        return $detected ?: null;
    }

    private function attachMenuItems(CmsPage $page, string $baseRoot): int
    {
        $absolute = rtrim($baseRoot, '/').$page->url;

        $items = CmsMenuItem::query()
            ->whereNull('page_id')
            ->whereIn('url', [$page->url, $absolute])
            ->get();

        $updated = 0;

        foreach ($items as $item) {
            $item->update([
                'page_id' => $page->id,
                'url' => null,
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);

            if ($page->parent_id === null && $item->parent_id) {
                $parentPageId = CmsMenuItem::query()
                    ->whereKey($item->parent_id)
                    ->value('page_id');

                if ($parentPageId) {
                    $page->update([
                        'parent_id' => $parentPageId,
                        'update_date' => now(),
                        'update_user' => self::SYSTEM_USER,
                    ]);
                }
            }

            $updated++;
        }

        return $updated;
    }
}
