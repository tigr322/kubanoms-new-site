<?php

namespace App\Services;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\Repositories\MenuRepository;
use App\Repositories\PageRepository;
use App\Repositories\SettingRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str as StrHelper;

class PageResolverService
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly MenuRepository $menuRepository,
        private readonly SettingRepository $settingRepository,
    ) {}

    public function buildViewModel(CmsPage $page): array
    {
        return [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'title_short' => $page->title_short,
                'content' => BannerSettingHelper::normalizeContent($page->content),
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'publication_date' => optional($page->publication_date)?->format('d.m.Y'),
                'page_status' => $page->page_status,
                'page_of_type' => $page->page_of_type,
                'url' => $page->url,
                'template' => $page->template,
                'path' => $page->path,
                'images' => collect($page->images ?? [])
                    ->filter()
                    ->map(fn (string $path): string => self::normalizeMediaPath($path))
                    ->values()
                    ->all(),
                'attachments' => collect($page->attachments ?? [])
                    ->filter()
                    ->map(fn (string $path): array => [
                        'name' => basename($path),
                        'url' => self::normalizeMediaPath($path),
                    ])
                    ->values()
                    ->all(),
            ],
            ...$this->layout(),
        ];
    }

    public function latestNewsAndDocuments(int $limit = 3): array
    {
        return [
            'news' => $this->pageRepository->latestByType(2, $limit),
            'documents' => $this->pageRepository->latestByType(3, $limit),
        ];
    }

    public function search(string $term, int $limit = 10): Collection
    {
        return $this->pageRepository->search($term, $limit);
    }

    public function layout(): array
    {
        $mapMenu = function (Collection $items) use (&$mapMenu): array {
            return $items->map(function ($item) use (&$mapMenu): array {
                $url = null;

                if ($item->page) {
                    $url = $item->page->page_status === PageStatus::PUBLISHED
                        ? $item->page->url
                        : null;
                }

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $url ?? $item->url,
                    'children' => $mapMenu($item->children ?? collect()),
                ];
            })->values()->all();
        };

        $settings = $this->settingRepository->getMany([
            'LEFT_SIDEBAR_BANNERS',
            'RIGHT_SIDEBAR_BANNERS',
            'RIGHT_SIDEBAR_MENU',
            'BOTTOM_BANNERS',
            'MAP',
            'EXTERNAL_LINKS',
            'LEFT_COLUMN',
            'CENTER_COLUMN',
            'RIGHT_COLUMN',
        ]);

        return [
            'menus' => [
                'navbar' => $mapMenu($this->menuRepository->getVisibleItems('NAVBAR')),
                'sidebar' => $mapMenu($this->menuRepository->getVisibleItems('SIDEBAR')),
                'current_information' => $mapMenu($this->menuRepository->getVisibleItems('CURRENT_INFORMATION')),
            ],
            'settings' => [
                'left_sidebar_banners' => $settings['LEFT_SIDEBAR_BANNERS'] ?? null,
                'right_sidebar_banners' => $settings['RIGHT_SIDEBAR_BANNERS'] ?? null,
                'right_sidebar_menu' => $settings['RIGHT_SIDEBAR_MENU'] ?? null,
                'bottom_banners' => $settings['BOTTOM_BANNERS'] ?? null,
                'map' => $settings['MAP'] ?? null,
                'external_links' => $settings['EXTERNAL_LINKS'] ?? null,
                'footer_left' => $settings['LEFT_COLUMN'] ?? null,
                'footer_center' => $settings['CENTER_COLUMN'] ?? null,
                'footer_right' => $settings['RIGHT_COLUMN'] ?? null,
            ],
        ];
    }

    private static function normalizeMediaPath(?string $path): string
    {
        if (! $path) {
            return '';
        }

        $clean = $path;

        if (preg_match('#https?://[^/]+(/storage/.*)$#', $path, $matches)) {
            $clean = $matches[1];
        }

        if (StrHelper::startsWith($clean, '//')) {
            return '/'.ltrim($clean, '/');
        }

        if (StrHelper::startsWith($clean, '/storage/')) {
            return $clean;
        }

        $normalized = preg_replace('#^public/#', '', ltrim($clean, '/'));

        return '/storage/'.$normalized;
    }
}
