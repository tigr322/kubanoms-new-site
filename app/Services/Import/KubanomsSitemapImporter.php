<?php

namespace App\Services\Import;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsPageDocument;
use App\PageStatus;
use App\PageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class KubanomsSitemapImporter
{
    private const string SYSTEM_USER = 'import:kubanoms';

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
     *     nodes_total: int,
     *     internal_links: int,
     *     external_links: int,
     *     file_links: int,
     *     pages_created: int,
     *     pages_existing: int,
     *     menu_items_created: int,
     *     menu_items_updated: int
     * }
     */
    public function import(
        string $sitemapUrl,
        string $menuName,
        bool $dryRun = false,
        bool $truncateMenu = false,
        bool $updateExisting = false,
    ): array {
        return $this->importFromUrl(
            sitemapUrl: $sitemapUrl,
            menuName: $menuName,
            dryRun: $dryRun,
            truncateMenu: $truncateMenu,
            updateExisting: $updateExisting,
        );
    }

    /**
     * @return array{
     *     nodes_total: int,
     *     internal_links: int,
     *     external_links: int,
     *     file_links: int,
     *     pages_created: int,
     *     pages_existing: int,
     *     menu_items_created: int,
     *     menu_items_updated: int
     * }
     */
    public function importFromUrl(
        string $sitemapUrl,
        string $menuName,
        bool $dryRun = false,
        bool $truncateMenu = false,
        bool $updateExisting = false,
        ?array $rootTitles = null,
    ): array {
        $html = $this->fetchHtml($sitemapUrl);
        $baseRoot = $this->baseRoot($sitemapUrl);

        return $this->importFromHtml(
            html: $html,
            baseRoot: $baseRoot,
            menuName: $menuName,
            dryRun: $dryRun,
            truncateMenu: $truncateMenu,
            updateExisting: $updateExisting,
            rootTitles: $rootTitles,
        );
    }

    /**
     * @return array{
     *     nodes_total: int,
     *     internal_links: int,
     *     external_links: int,
     *     file_links: int,
     *     pages_created: int,
     *     pages_existing: int,
     *     menu_items_created: int,
     *     menu_items_updated: int
     * }
     */
    public function importFromFile(
        string $filePath,
        string $baseUrl,
        string $menuName,
        bool $dryRun = false,
        bool $truncateMenu = false,
        bool $updateExisting = false,
        ?array $rootTitles = null,
    ): array {
        $resolved = $this->resolveFilePath($filePath);

        if (! is_file($resolved) || ! is_readable($resolved)) {
            throw new RuntimeException(sprintf('Файл карты сайта не найден: %s', $resolved));
        }

        $html = file_get_contents($resolved);

        if ($html === false) {
            throw new RuntimeException(sprintf('Не удалось прочитать файл карты сайта: %s', $resolved));
        }

        $baseRoot = $this->baseRoot($baseUrl);

        return $this->importFromHtml(
            html: $html,
            baseRoot: $baseRoot,
            menuName: $menuName,
            dryRun: $dryRun,
            truncateMenu: $truncateMenu,
            updateExisting: $updateExisting,
            rootTitles: $rootTitles,
        );
    }

    /**
     * @return array{
     *     nodes_total: int,
     *     internal_links: int,
     *     external_links: int,
     *     file_links: int,
     *     pages_created: int,
     *     pages_existing: int,
     *     menu_items_created: int,
     *     menu_items_updated: int
     * }
     */
    public function importFromHtml(
        string $html,
        string $baseRoot,
        string $menuName,
        bool $dryRun = false,
        bool $truncateMenu = false,
        bool $updateExisting = false,
        ?array $rootTitles = null,
    ): array {
        $normalizedHtml = $this->normalizeHtml($html);
        $tree = $this->parser->parse($normalizedHtml);

        if ($rootTitles !== null) {
            $tree = $this->filterTreeByRootTitles($tree, $rootTitles);
        }

        $stats = [
            'nodes_total' => 0,
            'internal_links' => 0,
            'external_links' => 0,
            'file_links' => 0,
            'pages_created' => 0,
            'pages_existing' => 0,
            'menu_items_created' => 0,
            'menu_items_updated' => 0,
        ];

        if (empty($tree)) {
            return $stats;
        }

        $baseHost = parse_url($baseRoot, PHP_URL_HOST) ?? '';

        if ($dryRun) {
            $this->collectStats($tree, $baseRoot, $baseHost, $stats);

            return $stats;
        }

        $menu = $this->resolveMenu($menuName);

        if (! $menu) {
            throw new RuntimeException(sprintf('Меню "%s" не найдено.', $menuName));
        }

        DB::transaction(function () use ($menu, $tree, $baseRoot, $baseHost, $truncateMenu, $updateExisting, &$stats): void {
            if ($truncateMenu) {
                CmsMenuItem::query()->where('menu_id', $menu->id)->delete();
            }

            $sortOrder = 1;

            foreach ($tree as $node) {
                $this->importNode(
                    menu: $menu,
                    node: $node,
                    sortOrder: $sortOrder,
                    parentMenuItem: null,
                    parentPage: null,
                    baseRoot: $baseRoot,
                    baseHost: $baseHost,
                    updateExisting: $updateExisting,
                    stats: $stats,
                );
                $sortOrder++;
            }
        });

        return $stats;
    }

    public function wipeStructure(): void
    {
        DB::transaction(function (): void {
            CmsMenuItem::query()->delete();
            CmsPageDocument::query()->delete();
            CmsPage::query()->delete();
        });
    }

    private function fetchHtml(string $sitemapUrl): string
    {
        $response = Http::retry(3, 250)
            ->timeout(30)
            ->withUserAgent('Mozilla/5.0 (compatible; KubanomsImporter/1.0)')
            ->get($sitemapUrl);

        if ($response->failed()) {
            throw new RuntimeException(sprintf('Не удалось загрузить карту сайта (%s).', $response->status()));
        }

        return $this->normalizeHtml($response->body());
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

    /**
     * @param  array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>  $tree
     * @param  array<int, string>  $rootTitles
     * @return array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>
     */
    private function filterTreeByRootTitles(array $tree, array $rootTitles): array
    {
        $normalizedTitles = array_map(fn (string $title): string => $this->normalizeTitle($title), $rootTitles);

        return array_values(array_filter(
            $tree,
            function (array $node) use ($normalizedTitles): bool {
                $title = $this->normalizeTitle((string) ($node['title'] ?? ''));

                foreach ($normalizedTitles as $needle) {
                    if ($needle === '') {
                        continue;
                    }

                    if ($title === $needle || Str::contains($title, $needle)) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    private function normalizeTitle(string $title): string
    {
        $clean = trim($title);
        $clean = str_replace(['ё', 'Ё'], 'е', $clean);
        $clean = mb_strtolower($clean);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return $clean;
    }

    private function resolveMenu(string $menuName): ?CmsMenu
    {
        return CmsMenu::query()
            ->where('name', $menuName)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>  $tree
     * @param  array<string, int>  $stats
     */
    private function collectStats(array $tree, string $baseRoot, string $baseHost, array &$stats): void
    {
        foreach ($tree as $node) {
            if ($this->isSkippableHref($node['href'])) {
                continue;
            }

            $stats['nodes_total']++;
            $link = $this->normalizeLink($node['href'], $baseRoot);
            $path = $this->pathFromUrl($link);

            if ($this->isFileLink($path)) {
                $stats['file_links']++;
            } elseif ($this->isInternalUrl($link, $baseHost)) {
                $stats['internal_links']++;
            } else {
                $stats['external_links']++;
            }

            if (! empty($node['children'])) {
                $this->collectStats($node['children'], $baseRoot, $baseHost, $stats);
            }
        }
    }

    /**
     * @param  array{title: string, href: string, children: array<int, array<string, mixed>>}  $node
     * @param  array<string, int>  $stats
     */
    private function importNode(
        CmsMenu $menu,
        array $node,
        int $sortOrder,
        ?CmsMenuItem $parentMenuItem,
        ?CmsPage $parentPage,
        string $baseRoot,
        string $baseHost,
        bool $updateExisting,
        array &$stats,
    ): void {
        $title = trim($node['title']);
        $href = trim($node['href']);

        if ($title === '' || $href === '' || $this->isSkippableHref($href)) {
            return;
        }

        $stats['nodes_total']++;

        $absoluteUrl = $this->normalizeLink($href, $baseRoot);
        $path = $this->pathFromUrl($absoluteUrl);
        $isInternal = $this->isInternalUrl($absoluteUrl, $baseHost);
        $isFile = $this->isFileLink($path);

        $page = null;
        $menuUrl = null;

        if ($isInternal && ! $isFile) {
            $stats['internal_links']++;
            $page = $this->upsertPage(
                url: $path,
                title: $title,
                parent: $parentPage,
                updateExisting: $updateExisting,
                stats: $stats,
            );
        } else {
            $menuUrl = $absoluteUrl;

            if ($isFile) {
                $stats['file_links']++;
            } else {
                $stats['external_links']++;
            }
        }

        $menuItem = $this->upsertMenuItem(
            menu: $menu,
            parent: $parentMenuItem,
            title: $title,
            page: $page,
            url: $menuUrl,
            sortOrder: $sortOrder,
            stats: $stats,
        );

        $childSortOrder = 1;
        $nextParentPage = $page ?? null;

        foreach ($node['children'] as $child) {
            $this->importNode(
                menu: $menu,
                node: $child,
                sortOrder: $childSortOrder,
                parentMenuItem: $menuItem,
                parentPage: $nextParentPage,
                baseRoot: $baseRoot,
                baseHost: $baseHost,
                updateExisting: $updateExisting,
                stats: $stats,
            );
            $childSortOrder++;
        }
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function upsertPage(
        string $url,
        string $title,
        ?CmsPage $parent,
        bool $updateExisting,
        array &$stats,
    ): CmsPage {
        $page = CmsPage::query()->where('url', $url)->first();

        if (! $page) {
            $stats['pages_created']++;

            return CmsPage::query()->create([
                'parent_id' => $parent?->id,
                'title' => $title,
                'title_short' => $title,
                'content' => null,
                'page_status' => PageStatus::PUBLISHED->value,
                'page_of_type' => PageType::PAGE->value,
                'template' => 'default',
                'url' => $url,
                'create_date' => now(),
                'create_user' => self::SYSTEM_USER,
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);
        }

        $stats['pages_existing']++;

        if ($updateExisting) {
            $page->update([
                'parent_id' => $parent?->id,
                'title' => $title,
                'title_short' => $title,
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);
        } elseif ($parent && $page->parent_id === null) {
            $page->update([
                'parent_id' => $parent->id,
                'update_date' => now(),
                'update_user' => self::SYSTEM_USER,
            ]);
        }

        return $page;
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function upsertMenuItem(
        CmsMenu $menu,
        ?CmsMenuItem $parent,
        string $title,
        ?CmsPage $page,
        ?string $url,
        int $sortOrder,
        array &$stats,
    ): CmsMenuItem {
        $item = CmsMenuItem::query()->firstOrNew([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'title' => $title,
        ]);

        $wasNew = ! $item->exists;

        $item->fill([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'title' => $title,
            'page_id' => $page?->id,
            'url' => $page ? null : $url,
            'sort_order' => $sortOrder,
            'visible' => true,
            'update_date' => now(),
            'update_user' => self::SYSTEM_USER,
        ]);

        if ($wasNew) {
            $item->fill([
                'create_date' => now(),
                'create_user' => self::SYSTEM_USER,
            ]);
        }

        $item->save();

        if ($wasNew) {
            $stats['menu_items_created']++;
        } else {
            $stats['menu_items_updated']++;
        }

        return $item;
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
}
