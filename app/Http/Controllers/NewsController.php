<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use App\Http\Requests\NewsIndexRequest;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use App\Services\PageResolverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->paginate(10)
            ->withQueryString()
            ->through($this->transformListItem());

        return Inertia::render('NewsArchive', [
            'page' => [
                ...$pageProps,
                'content' => BannerSettingHelper::normalizeContent((string) ($pageProps['content'] ?? '')),
            ],
            'news' => $this->paginatePayload($news),
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
            'image' => self::normalizeMediaPath(
                collect($item->images ?? [])->filter()->first(),
            ),
        ];
    }

    /**
     * @return array{
     *     data: array<int, array{id: int, title: string, url: string, date: string|null, image: string|null}>,
     *     links: array<int, array{url: string|null, label: string, active: bool}>,
     *     meta: array{current_page: int, per_page: int, total: int}
     * }
     */
    private function paginatePayload(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
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

        if (Str::startsWith($clean, '//')) {
            return '/'.ltrim($clean, '/');
        }

        if (Str::startsWith($clean, '/storage/')) {
            return $clean;
        }

        $normalized = preg_replace('#^public/#', '', ltrim($clean, '/'));

        return '/storage/'.$normalized;
    }
}
