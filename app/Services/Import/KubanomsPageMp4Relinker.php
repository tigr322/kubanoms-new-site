<?php

namespace App\Services\Import;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KubanomsPageMp4Relinker
{
    private const string SYSTEM_USER = 'import:kubanoms:relink-mp4';

    /**
     * @return array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }
     */
    public function relink(
        string $pageUrl,
        string $baseUrl,
        string $disk = 'public',
        string $fileDirectory = 'cms/page/videos',
        bool $collectLinks = true,
        ?callable $onStorageLink = null,
    ): array {
        $normalizedPageUrl = $this->normalizePageUrl($pageUrl);
        $page = $this->findPage($normalizedPageUrl);

        if (! $page) {
            throw new RuntimeException('Страница не найдена: '.$normalizedPageUrl);
        }

        $baseRoot = $this->baseRoot($baseUrl);
        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';

        if ($baseHost === '') {
            throw new RuntimeException('Невозможно определить host базового URL.');
        }

        $stats = [
            'page_url' => $page->url,
            'links_checked' => 0,
            'links_replaced' => 0,
            'files_downloaded' => 0,
            'files_failed' => 0,
            'files_skipped' => 0,
            'page_updated' => 0,
            'failed_links' => [],
            'storage_links' => [],
        ];

        $fileCache = [];
        $content = (string) ($page->content ?? '');
        $result = $this->relinkContent(
            html: $content,
            baseRoot: $baseRoot,
            baseHost: $baseHost,
            disk: $disk,
            fileDirectory: trim($fileDirectory, '/'),
            fileCache: $fileCache,
            stats: $stats,
            collectLinks: $collectLinks,
            onStorageLink: $onStorageLink,
        );

        if ($result['changed']) {
            $page->update([
                'content' => $result['content'],
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);
            $stats['page_updated'] = 1;
        }

        return $stats;
    }

    private function normalizePageUrl(string $url): string
    {
        $normalized = '/'.ltrim(trim($url), '/');

        if ($normalized === '/') {
            throw new RuntimeException('Укажите конкретный URL страницы.');
        }

        return $normalized;
    }

    private function findPage(string $pageUrl): ?CmsPage
    {
        $trimmed = rtrim($pageUrl, '/');
        $variants = array_values(array_unique([
            $pageUrl,
            $trimmed,
            $trimmed.'/',
        ]));

        return CmsPage::query()
            ->whereIn('url', $variants)
            ->first();
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }  $stats
     * @return array{changed: bool, content: string}
     */
    private function relinkContent(
        string $html,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
        bool $collectLinks,
        ?callable $onStorageLink,
    ): array {
        if (trim($html) === '') {
            return ['changed' => false, 'content' => $html];
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $prepared = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
        $wrapped = '<!doctype html><html><body><div id="__content_root">'.$prepared.'</div></body></html>';
        $dom->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $rootNode = $xpath->query('//*[@id="__content_root"]')?->item(0);

        if (! $rootNode instanceof DOMElement) {
            unset($xpath, $rootNode, $dom);

            return ['changed' => false, 'content' => $html];
        }

        $changed = false;

        foreach ($this->targetAttributes() as $target) {
            $selector = './/'.$target['tag'].'[@'.$target['attribute'].']';
            $nodes = $xpath->query($selector, $rootNode);

            if (! $nodes) {
                continue;
            }

            foreach ($this->toArray($nodes) as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $value = trim($node->getAttribute($target['attribute']));

                if ($value === '') {
                    continue;
                }

                $stats['links_checked']++;

                $normalized = $this->normalizeMp4Reference(
                    value: $value,
                    baseRoot: $baseRoot,
                    baseHost: $baseHost,
                    disk: $disk,
                    fileDirectory: $fileDirectory,
                    fileCache: $fileCache,
                    stats: $stats,
                    collectLinks: $collectLinks,
                    onStorageLink: $onStorageLink,
                );

                if ($normalized === $value) {
                    continue;
                }

                $node->setAttribute($target['attribute'], $normalized);
                $stats['links_replaced']++;
                $changed = true;
            }
        }

        if (! $changed) {
            unset($xpath, $rootNode, $dom);

            return ['changed' => false, 'content' => $html];
        }

        $content = trim($this->innerHtml($rootNode));
        unset($xpath, $rootNode, $dom);

        return ['changed' => true, 'content' => $content];
    }

    /**
     * @return array<int, array{tag: string, attribute: string}>
     */
    private function targetAttributes(): array
    {
        return [
            ['tag' => 'a', 'attribute' => 'href'],
            ['tag' => 'video', 'attribute' => 'src'],
            ['tag' => 'source', 'attribute' => 'src'],
        ];
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }  $stats
     */
    private function normalizeMp4Reference(
        string $value,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
        bool $collectLinks,
        ?callable $onStorageLink,
    ): string {
        if ($value === '' || Str::startsWith($value, ['#', 'mailto:', 'tel:', 'javascript:', 'data:', 'blob:'])) {
            return $value;
        }

        if (Str::startsWith($value, '/storage/')) {
            return $value;
        }

        $absolute = $this->absoluteUrl($value, $baseRoot);

        if ($absolute === '') {
            return $value;
        }

        $path = (string) (parse_url($absolute, PHP_URL_PATH) ?? '');

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'mp4') {
            return $value;
        }

        if (! $this->isInternalUrl($absolute, $baseHost)) {
            return $value;
        }

        return $this->downloadFile(
            originalValue: $value,
            absolute: $absolute,
            baseRoot: $baseRoot,
            disk: $disk,
            fileDirectory: $fileDirectory,
            fileCache: $fileCache,
            stats: $stats,
            collectLinks: $collectLinks,
            onStorageLink: $onStorageLink,
        );
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }  $stats
     */
    private function downloadFile(
        string $originalValue,
        string $absolute,
        string $baseRoot,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
        bool $collectLinks,
        ?callable $onStorageLink,
    ): string {
        if (isset($fileCache[$absolute])) {
            $stats['files_skipped']++;
            $this->trackStorageLink($stats, $fileCache[$absolute], $collectLinks, $onStorageLink);

            return $fileCache[$absolute];
        }

        $targetPath = $this->fileTargetPath($fileDirectory, $absolute, '', '');
        $existingStoragePath = $this->resolveExistingStoragePath($disk, $targetPath);

        if ($existingStoragePath !== null) {
            $fileCache[$absolute] = $existingStoragePath;
            $stats['files_skipped']++;
            $this->trackStorageLink($stats, $existingStoragePath, $collectLinks, $onStorageLink);

            return $existingStoragePath;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'kubanoms_mp4_');

        if (! is_string($tempFile) || $tempFile === '') {
            $stats['files_failed']++;
            $this->trackFailure($stats, $absolute, 'Не удалось создать временный файл');

            return $originalValue;
        }

        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(180)
                ->connectTimeout(15)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsPageMp4Relinker/1.0)')
                ->withHeaders([
                    'Accept' => '*/*',
                    'Referer' => rtrim($baseRoot, '/').$stats['page_url'],
                ])
                ->withOptions([
                    'sink' => $tempFile,
                ])
                ->get($absolute);

            if ($response->failed()) {
                $stats['files_failed']++;
                $this->trackFailure($stats, $absolute, 'HTTP '.$response->status());

                return $originalValue;
            }

            $contentType = (string) ($response->header('Content-Type') ?? '');
            $contentDisposition = (string) ($response->header('Content-Disposition') ?? '');

            if ($this->isHtmlResponse($contentType, $contentDisposition, $this->readFileSnippet($tempFile))) {
                $stats['files_skipped']++;

                return $originalValue;
            }

            $targetPath = $this->fileTargetPath($fileDirectory, $absolute, $contentType, $contentDisposition);
            $stream = fopen($tempFile, 'rb');

            if (! is_resource($stream)) {
                $stats['files_failed']++;
                $this->trackFailure($stats, $absolute, 'Не удалось открыть временный файл');

                return $originalValue;
            }

            try {
                $written = Storage::disk($disk)->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }

            if ($written === false) {
                $stats['files_failed']++;
                $this->trackFailure($stats, $absolute, 'Не удалось записать файл в storage');

                return $originalValue;
            }

            $url = Storage::disk($disk)->url($targetPath);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
            $originalName = $this->originalNameFromUrl($absolute, $contentDisposition);

            $fileCache[$absolute] = $urlPath;
            $stats['files_downloaded']++;
            $this->trackStorageLink($stats, $urlPath, $collectLinks, $onStorageLink);

            $this->upsertCmsFile(
                path: $targetPath,
                mimeType: $contentType,
                extension: strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)),
                originalName: $originalName,
            );

            return $urlPath;
        } catch (Throwable $exception) {
            $stats['files_failed']++;
            $this->trackFailure($stats, $absolute, $exception::class.': '.$exception->getMessage());

            return $originalValue;
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function isHtmlResponse(string $contentType, string $contentDisposition, string $body): bool
    {
        $normalizedContentType = strtolower(trim(strtok($contentType, ';') ?: ''));
        $normalizedDisposition = strtolower($contentDisposition);
        $hasAttachmentDisposition = Str::contains($normalizedDisposition, 'attachment');

        if ($hasAttachmentDisposition) {
            return false;
        }

        if (in_array($normalizedContentType, ['text/html', 'application/xhtml+xml'], true)) {
            return true;
        }

        $trimmed = ltrim($body);
        $startsWithHtml = Str::startsWith(
            strtolower(substr($trimmed, 0, 80)),
            ['<!doctype html', '<html', '<head', '<body'],
        );

        return $normalizedContentType === '' && $startsWithHtml;
    }

    private function fileTargetPath(
        string $fileDirectory,
        string $absoluteUrl,
        string $contentType,
        string $contentDisposition,
    ): string {
        $path = (string) (parse_url($absoluteUrl, PHP_URL_PATH) ?? '');
        $path = $this->normalizeStorageRelativePath($path);

        if ($path === '') {
            $path = 'video';
        }

        $path = $this->appendQuerySuffix($path, (string) (parse_url($absoluteUrl, PHP_URL_QUERY) ?? ''));
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === '') {
            $extension = $this->extensionFromContentDisposition($contentDisposition)
                ?? $this->extensionFromFileContentType($contentType);

            if ($extension !== '') {
                $path .= '.'.$extension;
            }
        }

        return trim($fileDirectory, '/').'/'.$path;
    }

    private function resolveExistingStoragePath(string $disk, string $targetPath): ?string
    {
        if ($targetPath === '' || ! Storage::disk($disk)->exists($targetPath)) {
            return null;
        }

        $url = Storage::disk($disk)->url($targetPath);
        $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;

        if (! is_string($urlPath) || trim($urlPath) === '') {
            return null;
        }

        return $urlPath;
    }

    private function normalizeStorageRelativePath(string $path): string
    {
        $segments = explode('/', str_replace('\\', '/', ltrim(trim($path), '/')));
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($normalized);

                continue;
            }

            $normalized[] = $segment;
        }

        return implode('/', $normalized);
    }

    private function extensionFromFileContentType(string $contentType): string
    {
        $type = strtolower(trim(strtok($contentType, ';') ?: ''));

        return match ($type) {
            'video/mp4' => 'mp4',
            default => 'bin',
        };
    }

    private function extensionFromContentDisposition(string $contentDisposition): ?string
    {
        if ($contentDisposition === '') {
            return null;
        }

        if (! preg_match("/filename\\*?=(?:UTF-8''|)?[\"']?([^\"';]+)/i", $contentDisposition, $matches)) {
            return null;
        }

        $filename = urldecode(trim($matches[1], "\"' "));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
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

    private function originalNameFromUrl(string $absoluteUrl, string $contentDisposition = ''): string
    {
        $extensionFromDisposition = $this->extensionFromContentDisposition($contentDisposition);
        $path = parse_url($absoluteUrl, PHP_URL_PATH) ?? '';
        $name = basename($path);

        if ($name === '' || $name === '/' || $name === '.') {
            $name = 'video';
        }

        $name = urldecode($name);
        $name = trim($name);

        if ($extensionFromDisposition !== null && pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= '.'.$extensionFromDisposition;
        }

        return $name;
    }

    private function upsertCmsFile(
        string $path,
        string $mimeType,
        string $extension,
        string $originalName,
    ): void {
        if ($path === '') {
            return;
        }

        $normalizedMime = trim($mimeType);

        if ($normalizedMime === '') {
            $normalizedMime = 'application/octet-stream';
        }

        $normalizedMime = substr($normalizedMime, 0, 25);
        $normalizedExtension = trim(strtolower($extension));

        if ($normalizedExtension === '') {
            $normalizedExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        if ($normalizedExtension === '') {
            $normalizedExtension = 'bin';
        }

        $normalizedName = trim($originalName);

        if ($normalizedName === '') {
            $normalizedName = basename($path);
        }

        $file = CmsFile::query()->where('path', $path)->first();

        if ($file) {
            $file->update([
                'original_name' => $normalizedName,
                'mime_type' => $normalizedMime,
                'extension' => $normalizedExtension,
                'description' => '',
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);

            return;
        }

        CmsFile::query()->create([
            'path' => $path,
            'original_name' => $normalizedName,
            'mime_type' => $normalizedMime,
            'extension' => $normalizedExtension,
            'description' => '',
            'create_date' => now(),
            'create_user' => self::SYSTEM_USER,
            'update_date' => now(),
            'update_user' => self::SYSTEM_USER,
        ]);
    }

    /**
     * @param  array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }  $stats
     */
    private function trackStorageLink(
        array &$stats,
        string $urlPath,
        bool $collectLinks,
        ?callable $onStorageLink,
    ): void {
        if ($urlPath === '') {
            return;
        }

        if ($onStorageLink !== null) {
            $onStorageLink($urlPath);
        }

        if ($collectLinks && ! in_array($urlPath, $stats['storage_links'], true)) {
            $stats['storage_links'][] = $urlPath;
        }
    }

    /**
     * @param  array{
     *     page_url: string,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     page_updated: int,
     *     failed_links: array<int, array{url: string, reason: string}>,
     *     storage_links: array<int, string>
     * }  $stats
     */
    private function trackFailure(array &$stats, string $url, string $reason): void
    {
        $normalizedUrl = trim($url);

        if ($normalizedUrl === '') {
            return;
        }

        $stats['failed_links'][] = [
            'url' => $normalizedUrl,
            'reason' => Str::limit(trim($reason), 300, '...'),
        ];
    }

    private function readFileSnippet(string $path, int $length = 512): string
    {
        if (! is_file($path)) {
            return '';
        }

        $handle = fopen($path, 'rb');

        if (! is_resource($handle)) {
            return '';
        }

        try {
            $content = fread($handle, $length);
        } finally {
            fclose($handle);
        }

        if (! is_string($content)) {
            return '';
        }

        return $content;
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

        if (Str::startsWith($url, '/')) {
            return rtrim($baseRoot, '/').$url;
        }

        return rtrim($baseRoot, '/').'/'.ltrim($url, '/');
    }

    private function isInternalUrl(string $url, string $baseHost): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return true;
        }

        $normalizedHost = strtolower($host);
        $normalizedBase = strtolower($baseHost);

        if ($normalizedHost === $normalizedBase) {
            return true;
        }

        if ($normalizedHost === 'www.'.$normalizedBase) {
            return true;
        }

        if (Str::startsWith($normalizedBase, 'www.')) {
            return Str::after($normalizedBase, 'www.') === $normalizedHost;
        }

        return false;
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
}
