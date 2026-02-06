<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KubanomsSitemapDownloader
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
    ];

    public function __construct(
        private readonly KubanomsSitemapParser $parser,
    ) {}

    /**
     * @return array{
     *     links_total: int,
     *     links_selected: int,
     *     downloaded: int,
     *     skipped: int,
     *     failed: int,
     *     output_dir: string
     * }
     */
    public function downloadFromFile(
        string $filePath,
        string $baseUrl,
        string $outputDir,
        bool $includeExternal = false,
        bool $includeFiles = false,
        ?int $limit = null,
    ): array {
        $resolved = $this->resolveFilePath($filePath);

        if (! is_file($resolved) || ! is_readable($resolved)) {
            throw new RuntimeException(sprintf('Файл карты сайта не найден: %s', $resolved));
        }

        $html = file_get_contents($resolved);

        if ($html === false) {
            throw new RuntimeException(sprintf('Не удалось прочитать файл карты сайта: %s', $resolved));
        }

        $normalizedHtml = $this->normalizeHtml($html);
        $tree = $this->parser->parse($normalizedHtml);
        $links = $this->uniqueLinks($tree);
        $baseRoot = $this->baseRoot($baseUrl);
        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';
        $outputDir = rtrim($this->resolveOutputPath($outputDir), '/');

        File::ensureDirectoryExists($outputDir);

        $stats = [
            'links_total' => count($links),
            'links_selected' => 0,
            'downloaded' => 0,
            'skipped' => 0,
            'failed' => 0,
            'output_dir' => $outputDir,
        ];

        foreach ($links as $href) {
            if ($this->isSkippableHref($href)) {
                $stats['skipped']++;

                continue;
            }

            $absoluteUrl = $this->normalizeLink($href, $baseRoot);

            if ($absoluteUrl === '' || Str::startsWith($absoluteUrl, ['mailto:', 'tel:'])) {
                $stats['skipped']++;

                continue;
            }

            $path = $this->pathFromUrl($absoluteUrl);
            $isFile = $this->isFileLink($path);
            $isInternal = $this->isInternalUrl($absoluteUrl, $baseHost);

            if (! $includeExternal && ! $isInternal) {
                $stats['skipped']++;

                continue;
            }

            if (! $includeFiles && $isFile) {
                $stats['skipped']++;

                continue;
            }

            if ($limit !== null && $stats['links_selected'] >= $limit) {
                break;
            }

            $stats['links_selected']++;
            $destination = $this->targetPath($outputDir, $absoluteUrl, $isInternal);

            try {
                $response = Http::retry(3, 250, throw: false)
                    ->timeout(30)
                    ->withUserAgent('Mozilla/5.0 (compatible; KubanomsSitemapDownloader/1.0)')
                    ->get($absoluteUrl);

                if ($response->failed()) {
                    $stats['failed']++;

                    continue;
                }

                File::ensureDirectoryExists(dirname($destination));
                $written = File::put($destination, $response->body());

                if ($written === false) {
                    $stats['failed']++;

                    continue;
                }

                $stats['downloaded']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>  $tree
     * @return array<int, string>
     */
    private function uniqueLinks(array $tree): array
    {
        $links = [];
        $this->collectLinks($tree, $links);

        return array_values(array_unique($links));
    }

    /**
     * @param  array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>  $tree
     * @param  array<int, string>  $links
     */
    private function collectLinks(array $tree, array &$links): void
    {
        foreach ($tree as $node) {
            if (isset($node['href']) && is_string($node['href']) && $node['href'] !== '') {
                $links[] = $node['href'];
            }

            if (! empty($node['children']) && is_array($node['children'])) {
                $this->collectLinks($node['children'], $links);
            }
        }
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

    private function resolveOutputPath(string $outputDir): string
    {
        $path = trim($outputDir);

        if ($path === '') {
            return $path;
        }

        if (Str::startsWith($path, ['/']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
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

    private function normalizeLink(string $href, string $baseRoot): string
    {
        $href = trim($href);

        if ($href === '') {
            return $href;
        }

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        if (Str::startsWith($href, '//')) {
            $scheme = parse_url($baseRoot, PHP_URL_SCHEME) ?? 'http';

            return $scheme.':'.$href;
        }

        if (Str::startsWith($href, 'mailto:') || Str::startsWith($href, 'tel:')) {
            return $href;
        }

        return rtrim($baseRoot, '/').'/'.ltrim($href, '/');
    }

    private function pathFromUrl(string $url): string
    {
        if (Str::startsWith($url, ['mailto:', 'tel:'])) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? '/' : $path;
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

    private function isSkippableHref(string $href): bool
    {
        return Str::startsWith(trim($href), ['#', 'javascript:']);
    }

    private function targetPath(string $outputDir, string $url, bool $isInternal): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        if ($path === '' || str_ends_with($path, '/')) {
            $path = rtrim($path, '/').'/index';
        }

        $relativePath = ltrim($path, '/');

        if ($relativePath === '') {
            $relativePath = 'index';
        }

        $relativePath = $this->ensureExtension($relativePath);
        $relativePath = $this->appendQuerySuffix($relativePath, $parts['query'] ?? '');

        if (! $isInternal) {
            $host = $parts['host'] ?? 'external';
            $relativePath = $host.'/'.$relativePath;
        }

        return rtrim($outputDir, '/').'/'.$relativePath;
    }

    private function ensureExtension(string $relativePath): string
    {
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        if ($extension !== '') {
            return $relativePath;
        }

        return $relativePath.'.html';
    }

    private function appendQuerySuffix(string $relativePath, string $query): string
    {
        if ($query === '') {
            return $relativePath;
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $query) ?? '';

        if ($safe === '') {
            return $relativePath;
        }

        $directory = pathinfo($relativePath, PATHINFO_DIRNAME);
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        $targetFilename = $filename.'__'.$safe;

        if ($extension !== '') {
            $targetFilename .= '.'.$extension;
        }

        if ($directory === '' || $directory === '.') {
            return $targetFilename;
        }

        return $directory.'/'.$targetFilename;
    }
}
