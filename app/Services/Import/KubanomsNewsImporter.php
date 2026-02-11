<?php

namespace App\Services\Import;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class KubanomsNewsImporter
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
     *     list_pages: int,
     *     list_items: int,
     *     details_failed: int,
     *     details_missing: int,
     *     pages_created: int,
     *     pages_updated: int,
     *     duplicates_skipped: int,
     *     files_downloaded: int,
     *     files_failed: int,
     *     files_skipped: int,
     *     images_downloaded: int,
     *     images_failed: int,
     *     images_skipped: int,
     *     menu_items_updated: int
     * }
     */
    public function importFromPages(
        int $startPage,
        int $endPage,
        string $baseUrl,
        string $disk = 'public',
        string $imageDirectory = 'cms/news/images',
        string $fileDirectory = 'cms/news/files',
        bool $downloadExternalFiles = false,
        bool $downloadDocuments = true,
        bool $downloadImages = true,
        bool $updateExistingMeta = false,
        ?string $parentUrl = null,
    ): array {
        if ($startPage < 1) {
            $startPage = 1;
        }

        if ($endPage < $startPage) {
            [$startPage, $endPage] = [$endPage, $startPage];
        }

        $baseRoot = $this->baseRoot($baseUrl);
        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';
        $imageDirectory = trim($imageDirectory, '/');
        $fileDirectory = trim($fileDirectory, '/');
        $parentPageId = $this->resolveParentPageId($parentUrl, $baseRoot);
        $imageCache = [];
        $fileCache = [];
        $seenUrls = [];

        $stats = [
            'list_pages' => 0,
            'list_items' => 0,
            'details_failed' => 0,
            'details_missing' => 0,
            'pages_created' => 0,
            'pages_updated' => 0,
            'duplicates_skipped' => 0,
            'files_downloaded' => 0,
            'files_failed' => 0,
            'files_skipped' => 0,
            'document_links' => [],
            'images_downloaded' => 0,
            'images_failed' => 0,
            'images_skipped' => 0,
            'image_links' => [],
            'menu_items_updated' => 0,
        ];

        $listSignatures = [];

        for ($page = $startPage; $page <= $endPage; $page++) {
            $listPage = $this->fetchListPage($page, $baseRoot, $listSignatures);

            if (! $listPage) {
                continue;
            }

            $listSignatures[] = $listPage['signature'];
            $stats['list_pages']++;
            $items = $listPage['items'];
            $stats['list_items'] += count($items);

            foreach ($items as $item) {
                $detailUrl = $item['url'];
                $detailPath = $this->pathFromUrl($detailUrl);

                if (! $detailPath) {
                    continue;
                }

                if (isset($seenUrls[$detailPath])) {
                    $stats['duplicates_skipped']++;

                    continue;
                }

                $seenUrls[$detailPath] = true;

                $detailHtml = $this->fetchHtml($detailUrl);

                if ($detailHtml === null) {
                    $stats['details_failed']++;

                    continue;
                }

                $parsed = $this->parseDetailContent(
                    html: $detailHtml,
                    baseRoot: $baseRoot,
                    baseHost: $baseHost,
                    disk: $disk,
                    imageDirectory: $imageDirectory,
                    fileDirectory: $fileDirectory,
                    downloadExternalFiles: $downloadExternalFiles,
                    downloadDocuments: $downloadDocuments,
                    downloadImages: $downloadImages,
                    imageCache: $imageCache,
                    fileCache: $fileCache,
                    stats: $stats,
                );

                if (! $parsed) {
                    $stats['details_missing']++;

                    continue;
                }

                $images = $parsed['images'];
                $previewImage = $item['preview_image'];

                if ($downloadImages && empty($images) && $previewImage) {
                    $previewPath = $this->downloadImage(
                        src: $previewImage,
                        baseRoot: $baseRoot,
                        baseHost: $baseHost,
                        disk: $disk,
                        imageDirectory: $imageDirectory,
                        imageCache: $imageCache,
                        stats: $stats,
                    );

                    if ($previewPath !== '') {
                        $images[] = $previewPath;
                        $parsed['content'] = $this->prependImageToContent($parsed['content'], $previewPath);
                    }
                }

                $images = array_values(array_unique(array_filter($images)));
                $title = $parsed['title'] ?: $item['title'];
                $publicationDate = $item['date'] ?? $parsed['date'];

                $pageModel = CmsPage::query()->where('url', $detailPath)->first();

                if (! $pageModel) {
                    $pageModel = CmsPage::query()->create([
                        'parent_id' => $parentPageId,
                        'title' => $title,
                        'title_short' => $title,
                        'content' => $parsed['content'],
                        'page_status' => PageStatus::PUBLISHED->value,
                        'page_of_type' => PageType::NEWS->value,
                        'template' => 'news',
                        'url' => $detailPath,
                        'publication_date' => $publicationDate,
                        'meta_description' => $parsed['meta_description'],
                        'meta_keywords' => $parsed['meta_keywords'],
                        'images' => $images,
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
                        'images' => $images,
                    ];

                    if ($publicationDate) {
                        $updates['publication_date'] = $publicationDate;
                    }

                    if ($updateExistingMeta || $pageModel->title === null || $pageModel->title === '') {
                        $updates['title'] = $title;
                        $updates['title_short'] = $title;
                    }

                    if ($updateExistingMeta || ! $pageModel->meta_description) {
                        $updates['meta_description'] = $parsed['meta_description'];
                    }

                    if ($updateExistingMeta || ! $pageModel->meta_keywords) {
                        $updates['meta_keywords'] = $parsed['meta_keywords'];
                    }

                    if ($parentPageId && ! $pageModel->parent_id) {
                        $updates['parent_id'] = $parentPageId;
                    }

                    $pageModel->update($updates);
                    $stats['pages_updated']++;
                }

                $stats['menu_items_updated'] += $this->attachMenuItems($pageModel, $baseRoot);
            }
        }

        return $stats;
    }

    /**
     * @param  array<int, string>  $knownSignatures
     * @return array{
     *     html: string,
     *     items: array<int, array{title: string, url: string, date: ?\Carbon\Carbon, preview_image: ?string}>,
     *     signature: string
     * }|null
     */
    private function fetchListPage(int $page, string $baseRoot, array $knownSignatures): ?array
    {
        $fallbackDuplicate = null;

        foreach ($this->listPageCandidates($page, $baseRoot) as $url) {
            $html = $this->fetchHtml($url);

            if ($html === null) {
                continue;
            }

            $items = $this->parseListItems($html, $baseRoot);

            if ($items === []) {
                continue;
            }

            $signature = $this->listItemsSignature($items);
            $payload = [
                'html' => $html,
                'items' => $items,
                'signature' => $signature,
            ];

            if (! in_array($signature, $knownSignatures, true)) {
                return $payload;
            }

            if ($fallbackDuplicate === null) {
                $fallbackDuplicate = $payload;
            }
        }

        return $fallbackDuplicate;
    }

    /**
     * @return array<int, string>
     */
    private function listPageCandidates(int $page, string $baseRoot): array
    {
        $root = rtrim($baseRoot, '/');
        $candidates = [
            $root.'/newslist/?page='.$page,
            $root.'/newslist/?PAGEN_1='.$page,
        ];

        if ($page === 1) {
            $candidates[] = $root.'/newslist/';
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<int, array{title: string, url: string, date: ?\Carbon\Carbon, preview_image: ?string}>  $items
     */
    private function listItemsSignature(array $items): string
    {
        $urls = [];

        foreach ($items as $item) {
            $url = (string) ($item['url'] ?? '');

            if ($url !== '') {
                $urls[] = $url;
            }
        }

        sort($urls);

        return sha1(implode('|', $urls));
    }

    /**
     * @return array<int, array{title: string, url: string, date: ?\Carbon\Carbon, preview_image: ?string}>
     */
    private function parseListItems(string $html, string $baseRoot): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $prepared = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
        $dom->loadHTML($prepared, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $items = [];
        $newsNodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " news ")]');

        if (! $newsNodes) {
            return $items;
        }

        foreach ($this->toArray($newsNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $titleNode = $xpath->query('.//h3[contains(@class,"titlenews")]/a', $node)?->item(0);

            if (! $titleNode instanceof DOMElement) {
                continue;
            }

            $title = trim(preg_replace('/\s+/u', ' ', $titleNode->textContent ?? '') ?? '');
            $href = trim($titleNode->getAttribute('href'));

            if ($title === '' || $href === '') {
                continue;
            }

            $dateNode = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " date ")]', $node)?->item(0);
            $date = $this->parseDate($dateNode?->textContent);

            $imgNode = $xpath->query('.//img[1]', $node)?->item(0);
            $previewImage = $imgNode instanceof DOMElement ? trim($imgNode->getAttribute('src')) : null;

            $items[] = [
                'title' => $title,
                'url' => $this->absoluteUrl($href, $baseRoot),
                'date' => $date,
                'preview_image' => $previewImage !== '' ? $previewImage : null,
            ];
        }

        return $items;
    }

    /**
     * @return array{title: string|null, content: string, meta_description: string|null, meta_keywords: string|null, images: array<int, string>, date: ?\Carbon\Carbon}|null
     */
    private function parseDetailContent(
        string $html,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $imageDirectory,
        string $fileDirectory,
        bool $downloadExternalFiles,
        bool $downloadDocuments,
        bool $downloadImages,
        array &$imageCache,
        array &$fileCache,
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
        $date = $this->extractDate($xpath, $contentNode);

        $this->removeNodesByTag($contentNode, ['script', 'style']);
        $this->removeNodesBySelector($xpath, './/div[contains(@class,"print")]', $contentNode);
        $this->removeNodesBySelector($xpath, './/div[@id="status"]', $contentNode);
        $this->removeNodesBySelector($xpath, './/div[contains(concat(" ", normalize-space(@class), " "), " date ")]', $contentNode);

        $this->normalizeLinks(
            contentNode: $contentNode,
            baseRoot: $baseRoot,
            baseHost: $baseHost,
            disk: $disk,
            fileDirectory: $fileDirectory,
            downloadExternalFiles: $downloadExternalFiles,
            downloadDocuments: $downloadDocuments,
            fileCache: $fileCache,
            stats: $stats,
        );
        $this->normalizeForms(
            contentNode: $contentNode,
            baseRoot: $baseRoot,
            baseHost: $baseHost,
            disk: $disk,
            fileDirectory: $fileDirectory,
            downloadExternalFiles: $downloadExternalFiles,
            downloadDocuments: $downloadDocuments,
            fileCache: $fileCache,
            stats: $stats,
        );
        $this->normalizeMediaSources($contentNode, $baseRoot);

        $images = [];
        $fallbackImageSrc = $this->extractFallbackImageSource($xpath, $contentNode);

        if ($downloadImages) {
            $this->normalizeImages(
                contentNode: $contentNode,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                imageDirectory: $imageDirectory,
                imageCache: $imageCache,
                stats: $stats,
                images: $images,
            );

            if ($images === [] && $fallbackImageSrc) {
                $fallbackImage = $this->downloadImage(
                    src: $fallbackImageSrc,
                    baseRoot: $baseRoot,
                    baseHost: $baseHost,
                    disk: $disk,
                    imageDirectory: $imageDirectory,
                    imageCache: $imageCache,
                    stats: $stats,
                );

                if ($fallbackImage !== '') {
                    $images[] = $fallbackImage;
                }
            }
        } else {
            $this->normalizeImageSourcesWithoutDownload($contentNode, $baseRoot);

            if ($fallbackImageSrc) {
                $normalizedFallback = $this->normalizeExternalSrc($fallbackImageSrc, $baseRoot);

                if ($normalizedFallback !== '') {
                    $images[] = $normalizedFallback;
                }
            }
        }

        $contentHtml = trim($this->innerHtml($contentNode));

        if ($images !== []) {
            $contentHtml = $this->prependImageToContent($contentHtml, $images[0]);
        }

        return [
            'title' => $title,
            'content' => $contentHtml,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'images' => $images,
            'date' => $date,
        ];
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsNewsImporter/1.0)')
                ->get($url);

            if ($response->failed()) {
                return null;
            }

            return $this->normalizeHtml($response->body());
        } catch (Throwable $exception) {
            return null;
        }
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

    private function extractDate(DOMXPath $xpath, DOMElement $contentNode): ?Carbon
    {
        $node = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " date ")]', $contentNode)?->item(0);

        return $this->parseDate($node?->textContent);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($clean === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d.m.Y', $clean);
        } catch (Throwable $exception) {
            return null;
        }
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

    private function normalizeLinks(
        DOMElement $contentNode,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        bool $downloadExternalFiles,
        bool $downloadDocuments,
        array &$fileCache,
        array &$stats,
    ): void {
        foreach ($this->toArray($contentNode->getElementsByTagName('a')) as $link) {
            $href = $link->getAttribute('href');

            if ($href === '') {
                continue;
            }

            $normalized = $this->normalizeHref(
                href: $href,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                fileDirectory: $fileDirectory,
                downloadExternalFiles: $downloadExternalFiles,
                downloadDocuments: $downloadDocuments,
                fileCache: $fileCache,
                stats: $stats,
            );

            if ($normalized !== '') {
                $link->setAttribute('href', $normalized);
            }
        }
    }

    private function normalizeForms(
        DOMElement $contentNode,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        bool $downloadExternalFiles,
        bool $downloadDocuments,
        array &$fileCache,
        array &$stats,
    ): void {
        foreach ($this->toArray($contentNode->getElementsByTagName('form')) as $form) {
            $action = $form->getAttribute('action');

            if ($action === '') {
                continue;
            }

            $normalized = $this->normalizeHref(
                href: $action,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                fileDirectory: $fileDirectory,
                downloadExternalFiles: $downloadExternalFiles,
                downloadDocuments: $downloadDocuments,
                fileCache: $fileCache,
                stats: $stats,
            );

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
        array &$images,
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
                $images[] = $normalized;
            }
        }
    }

    private function normalizeImageSourcesWithoutDownload(DOMElement $contentNode, string $baseRoot): void
    {
        foreach ($this->toArray($contentNode->getElementsByTagName('img')) as $image) {
            $src = $image->getAttribute('src');

            if ($src === '') {
                continue;
            }

            $normalized = $this->normalizeExternalSrc($src, $baseRoot);

            if ($normalized !== '') {
                $image->setAttribute('src', $normalized);
            }
        }
    }

    private function normalizeHref(
        string $href,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        bool $downloadExternalFiles,
        bool $downloadDocuments,
        array &$fileCache,
        array &$stats,
    ): string {
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

        $isInternalUrl = $this->isInternalUrl($absolute, $baseHost);

        if ($downloadDocuments && $this->isFileLink($path) && ($isInternalUrl || $downloadExternalFiles)) {
            return $this->downloadFile(
                src: $href,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                disk: $disk,
                fileDirectory: $fileDirectory,
                downloadExternalFiles: $downloadExternalFiles,
                downloadDocuments: $downloadDocuments,
                fileCache: $fileCache,
                stats: $stats,
            );
        }

        if ($isInternalUrl) {
            $normalized = '/'.ltrim($path, '/');

            if ($query) {
                $normalized .= '?'.$query;
            }

            return $normalized;
        }

        return $absolute;
    }

    private function downloadFile(
        string $src,
        string $baseRoot,
        string $baseHost,
        string $disk,
        string $fileDirectory,
        bool $downloadExternalFiles,
        bool $downloadDocuments,
        array &$fileCache,
        array &$stats,
    ): string {
        if (! $downloadDocuments) {
            $stats['files_skipped']++;

            return $src;
        }

        $src = trim($src);

        if ($src === '' || Str::startsWith($src, ['data:', 'blob:'])) {
            $stats['files_skipped']++;

            return $src;
        }

        $absolute = $this->absoluteUrl($src, $baseRoot);

        if ($absolute === '') {
            $stats['files_skipped']++;

            return $src;
        }

        if (! $this->isInternalUrl($absolute, $baseHost) && ! $downloadExternalFiles) {
            $stats['files_skipped']++;

            return $absolute;
        }

        if (isset($fileCache[$absolute])) {
            $this->trackStorageLink($stats, 'document_links', $fileCache[$absolute]);

            return $fileCache[$absolute];
        }

        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsNewsImporter/1.0)')
                ->get($absolute);

            if ($response->failed()) {
                $stats['files_failed']++;

                return $absolute;
            }

            $contentType = (string) ($response->header('Content-Type') ?? '');
            $contentDisposition = (string) ($response->header('Content-Disposition') ?? '');
            $targetPath = $this->fileTargetPath($fileDirectory, $absolute, $contentType, $contentDisposition);
            Storage::disk($disk)->put($targetPath, $response->body());
            $url = Storage::disk($disk)->url($targetPath);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
            $originalName = $this->originalNameFromUrl($absolute, $contentDisposition);

            $fileCache[$absolute] = $urlPath;
            $stats['files_downloaded']++;
            $this->trackStorageLink($stats, 'document_links', $urlPath);
            $this->upsertCmsFile(
                path: $targetPath,
                mimeType: $contentType,
                extension: strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)),
                originalName: $originalName,
            );

            return $urlPath;
        } catch (Throwable $exception) {
            $stats['files_failed']++;

            return $absolute;
        }
    }

    private function normalizeExternalSrc(string $src, string $baseRoot): string
    {
        $src = trim($src);

        if ($src === '' || Str::startsWith($src, ['data:', 'blob:'])) {
            return $src;
        }

        return $this->absoluteUrl($src, $baseRoot);
    }

    private function prependImageToContent(string $content, string $imageSrc): string
    {
        $content = trim($content);
        $imageSrc = trim($imageSrc);

        if ($imageSrc === '') {
            return $content;
        }

        if ($content !== '' && str_contains($content, 'src="'.$imageSrc.'"')) {
            return $content;
        }

        $imageHtml = '<p><img src="'.$imageSrc.'" alt="" /></p>';

        if ($content === '') {
            return $imageHtml;
        }

        return $imageHtml.PHP_EOL.$content;
    }

    private function extractFallbackImageSource(DOMXPath $xpath, DOMElement $contentNode): ?string
    {
        $candidates = $xpath->query('./ancestor::tr[1]//img[@src]', $contentNode);

        if (! $candidates) {
            return null;
        }

        foreach ($this->toArray($candidates) as $candidate) {
            if (! $candidate instanceof DOMElement) {
                continue;
            }

            if ($this->isDescendantOf($candidate, $contentNode)) {
                continue;
            }

            $src = trim($candidate->getAttribute('src'));

            if ($src === '' || ! $this->isLikelyArticleImage($src)) {
                continue;
            }

            return $src;
        }

        return null;
    }

    private function isDescendantOf(DOMElement $node, DOMElement $ancestor): bool
    {
        $parent = $node->parentNode;

        while ($parent instanceof DOMElement) {
            if ($parent->isSameNode($ancestor)) {
                return true;
            }

            $parent = $parent->parentNode;
        }

        return false;
    }

    private function isLikelyArticleImage(string $src): bool
    {
        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return false;
        }

        $lower = strtolower($path);

        foreach ([
            'logo',
            'home.gif',
            'map.gif',
            'mail.gif',
            'search.gif',
            'print.gif',
            'totop.gif',
            'topmenu',
            'left_top',
            'left_bottom',
            'right_top',
            'right_bottom',
            'div.gif',
            'line.gif',
            'spacer.gif',
        ] as $noise) {
            if (str_contains($lower, $noise)) {
                return false;
            }
        }

        return true;
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
            $this->trackStorageLink($stats, 'image_links', $imageCache[$absolute]);

            return $imageCache[$absolute];
        }

        try {
            $response = Http::retry(3, 250, throw: false)
                ->timeout(30)
                ->withUserAgent('Mozilla/5.0 (compatible; KubanomsNewsImporter/1.0)')
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
            $originalName = $this->originalNameFromUrl($absolute);

            $imageCache[$absolute] = $urlPath;
            $stats['images_downloaded']++;
            $this->trackStorageLink($stats, 'image_links', $urlPath);
            $this->upsertCmsFile(
                path: $targetPath,
                mimeType: $contentType,
                extension: strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)),
                originalName: $originalName,
            );

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

    private function trackStorageLink(array &$stats, string $key, string $urlPath): void
    {
        if ($urlPath === '') {
            return;
        }

        if (! isset($stats[$key]) || ! is_array($stats[$key])) {
            $stats[$key] = [];
        }

        if (! in_array($urlPath, $stats[$key], true)) {
            $stats[$key][] = $urlPath;
        }
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

    private function pathFromUrl(string $url): ?string
    {
        if (Str::startsWith($url, ['mailto:', 'tel:'])) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($path === '') {
            return null;
        }

        $path = '/'.ltrim($path, '/');

        return $path === '' ? null : $path;
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

    private function baseRoot(string $url): string
    {
        $parts = parse_url($url);

        if (! $parts || ! isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('Невозможно определить базовый URL.');
        }

        return $parts['scheme'].'://'.$parts['host'];
    }

    private function resolveParentPageId(?string $parentUrl, string $baseRoot): ?int
    {
        $normalized = $parentUrl !== null && $parentUrl !== ''
            ? $this->normalizeParentUrl($parentUrl)
            : '/newslist';

        $normalized = rtrim($normalized, '/');
        $variants = [$normalized, $normalized.'/', $normalized.'/index.html'];

        $page = CmsPage::query()
            ->whereIn('url', $variants)
            ->first();

        if ($page) {
            return $page->id;
        }

        $page = CmsPage::query()->create([
            'parent_id' => null,
            'title' => 'Новости',
            'title_short' => 'Новости',
            'content' => null,
            'page_status' => PageStatus::PUBLISHED->value,
            'page_of_type' => PageType::PAGE->value,
            'template' => 'default',
            'url' => $variants[0],
            'create_date' => now(),
            'create_user' => self::SYSTEM_USER,
            'update_date' => now(),
            'update_user' => self::SYSTEM_USER,
        ]);

        $this->attachMenuItems($page, $baseRoot);

        return $page->id;
    }

    private function normalizeParentUrl(string $url): string
    {
        $normalized = '/'.ltrim($url, '/');

        return $normalized;
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
