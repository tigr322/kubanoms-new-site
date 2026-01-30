<?php

namespace App\Repositories;

use App\Models\Cms\CmsPage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageRepository
{
    public function findHome(): ?CmsPage
    {
        return CmsPage::query()
            ->where('url', '/')
            ->orWhere('template', 'home')
            ->first();
    }

    public function findByUrl(string $url): ?CmsPage
    {
        $normalized = '/'.ltrim($url, '/');

        return CmsPage::query()
            ->where('url', $normalized)
            ->first();
    }

    public function latestByType(int $pageType, int $limit = 3): Collection
    {
        return CmsPage::query()
            ->where('page_of_type', $pageType)
            ->where('page_status', 3)
            ->where('template', 'news') // Добавляем фильтр по шаблону news
            ->orderByRaw('CASE WHEN images IS NULL OR json_array_length(images) = 0 THEN 1 ELSE 0 END')
            ->orderByDesc('publication_date')
            ->limit($limit)
            ->get();
    }

    public function search(string $term, int $limit = 10): Collection
    {
        return CmsPage::query()
            ->where('page_status', 3)
            ->where(function ($query) use ($term): void {
                $query->where('title', 'like', '%'.$term.'%')
                    ->orWhere('content', 'like', '%'.$term.'%');
            })
            ->orderByDesc('publication_date')
            ->limit($limit)
            ->get();
    }

    public function requirePublishedByUrl(string $url): CmsPage
    {
        $page = $this->findByUrl($url);

        if (! $page) {
            throw new ModelNotFoundException('Page not found');
        }

        // Проверяем статус страницы с учетом enum
        $pageStatus = $page->page_status;
        if ($pageStatus instanceof \App\PageStatus) {
            // Если это enum, сравниваем с enum значением
            if ($pageStatus !== \App\PageStatus::PUBLISHED) {
                throw new ModelNotFoundException('Page not published');
            }
        } else {
            // Если это старое значение (int), сравниваем с числом
            if ((int) $pageStatus !== 3) {
                throw new ModelNotFoundException('Page not published');
            }
        }

        return $page;
    }
}
