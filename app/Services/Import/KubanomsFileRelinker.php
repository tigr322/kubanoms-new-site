<?php

namespace App\Services\Import;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KubanomsFileRelinker
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

    private const string SYSTEM_USER = 'import:kubanoms:relink-files';

    /**
     * @param  array<int, int>  $pageIds
     * @return array{
     *     pages_total: int,
     *     pages_processed: int,
     *     pages_updated: int,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     document_links: array<int, string>
     * }
     */
    public function relink(
        string $baseUrl,
        string $disk = 'public',
        string $fileDirectory = 'cms/page/files',
        ?int $limit = null,
        array $pageIds = [],
    ): array {
        $baseRoot = $this->baseRoot($baseUrl);
        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';

        if ($baseHost === '') {
            throw new RuntimeException('Невозможно определить host базового URL.');
        }

        $stats = [
            'pages_total' => 0,
            'pages_processed' => 0,
            'pages_updated' => 0,
            'links_checked' => 0,
            'links_replaced' => 0,
            'files_downloaded' => 0,
            'files_failed' => 0,
            'files_skipped' => 0,
            'document_links' => [],
        ];

        $query = $this->pagesQuery($pageIds);
        $stats['pages_total'] = (clone $query)->count();

        $processed = 0;
        $fileCache = [];
        $fileDirectory = trim($fileDirectory, '/');

        foreach ($query->orderBy('id')->cursor() as $page) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $processed++;
            $stats['pages_processed']++;

            $result = $this->relinkPageContent(
                html: (string) $page->content,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                fileDirectory: $fileDirectory,
                fileCache: $fileCache,
                stats: $stats,
            );

            if (! $result['changed']) {
                continue;
            }

            $page->update([
                'content' => $result['content'],
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);

            $stats['pages_updated']++;
        }

        return $stats;
    }

    /**
     * @param  array<int, int>  $pageIds
     */
    private function pagesQuery(array $pageIds): Builder
    {
        $query = CmsPage::query()
            ->whereNotNull('content')
            ->where('content', '!=', '');

        if ($pageIds !== []) {
            $query->whereIn('id', $pageIds);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     pages_total: int,
     *     pages_processed: int,
     *     pages_updated: int,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     document_links: array<int, string>
     * }  $stats
     * @return array{changed: bool, content: string}
     */
    private function relinkPageContent(
        string $html,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
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
            return ['changed' => false, 'content' => $html];
        }

        $changed = false;

        foreach ($this->toArray($rootNode->getElementsByTagName('a')) as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $href = trim($link->getAttribute('href'));

            if ($href === '') {
                continue;
            }

            $stats['links_checked']++;

            $normalized = $this->normalizeHref(
                href: $href,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                fileDirectory: $fileDirectory,
                fileCache: $fileCache,
                stats: $stats,
            );

            if ($normalized === $href) {
                continue;
            }

            $link->setAttribute('href', $normalized);
            $stats['links_replaced']++;
            $changed = true;
        }

        if (! $changed) {
            return ['changed' => false, 'content' => $html];
        }

        return ['changed' => true, 'content' => trim($this->innerHtml($rootNode))];
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     pages_total: int,
     *     pages_processed: int,
     *     pages_updated: int,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     document_links: array<int, string>
     * }  $stats
     */
    private function normalizeHref(
        string $href,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
    ): string {
        if ($href === '' || Str::startsWith($href, ['#', 'mailto:', 'tel:', 'javascript:', 'data:', 'blob:'])) {
            return $href;
        }

        if (Str::startsWith($href, '/storage/')) {
            return $href;
        }

        $absolute = $this->absoluteUrl($href, $baseRoot);

        if ($absolute === '') {
            return $href;
        }

        $path = (string) (parse_url($absolute, PHP_URL_PATH) ?? '');

        if (! $this->isFileLink($path)) {
            return $href;
        }

        if (! $this->isInternalUrl($absolute, $baseHost)) {
            return $href;
        }

        return $this->downloadFile(
            href: $href,
            absolute: $absolute,
            disk: $disk,
            fileDirectory: $fileDirectory,
            fileCache: $fileCache,
            stats: $stats,
        );
    }

    /**
     * @param  array<string, string>  $fileCache
     * @param  array{
     *     pages_total: int,
     *     pages_processed: int,
     *     pages_updated: int,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     document_links: array<int, string>
     * }  $stats
     */
    private function downloadFile(
        string $href,
        string $absolute,
        string $disk,
        string $fileDirectory,
        array &$fileCache,
        array &$stats,
    ): string {
        if (isset($fileCache[$absolute])) {
            $stats['files_skipped']++;
            $this->trackStorageLink($stats, $fileCache[$absolute]);

            return $fileCache[$absolute];
        }

        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsFileRelinker/1.0)')
                ->get($absolute);

            if ($response->failed()) {
                $stats['files_failed']++;

                return $href;
            }

            $contentType = (string) ($response->header('Content-Type') ?? '');
            $contentDisposition = (string) ($response->header('Content-Disposition') ?? '');

            if ($this->isHtmlResponse($contentType, $contentDisposition, $response->body())) {
                $stats['files_skipped']++;

                return $href;
            }

            $targetPath = $this->fileTargetPath($fileDirectory, $absolute, $contentType, $contentDisposition);
            Storage::disk($disk)->put($targetPath, $response->body());

            $url = Storage::disk($disk)->url($targetPath);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
            $originalName = $this->originalNameFromUrl($absolute, $contentDisposition);

            $fileCache[$absolute] = $urlPath;
            $stats['files_downloaded']++;
            $this->trackStorageLink($stats, $urlPath);

            $this->upsertCmsFile(
                path: $targetPath,
                mimeType: $contentType,
                extension: strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)),
                originalName: $originalName,
            );

            return $urlPath;
        } catch (Throwable $exception) {
            $stats['files_failed']++;

            return $href;
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
        $path = parse_url($absoluteUrl, PHP_URL_PATH) ?? '';
        $path = ltrim($path, '/');
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        if ($path === '') {
            $path = 'file';
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

    private function extensionFromFileContentType(string $contentType): string
    {
        $type = strtolower(trim(strtok($contentType, ';') ?: ''));

        return match ($type) {
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'application/rtf', 'text/rtf' => 'rtf',
            'application/zip' => 'zip',
            'application/x-rar-compressed', 'application/vnd.rar' => 'rar',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
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
            $name = 'file';
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
     *     pages_total: int,
     *     pages_processed: int,
     *     pages_updated: int,
     *     links_checked: int,
     *     links_replaced: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     document_links: array<int, string>
     * }  $stats
     */
    private function trackStorageLink(array &$stats, string $urlPath): void
    {
        if ($urlPath === '') {
            return;
        }

        if (! in_array($urlPath, $stats['document_links'], true)) {
            $stats['document_links'][] = $urlPath;
        }
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

    private function isFileLink(string $path): bool
    {
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
}
