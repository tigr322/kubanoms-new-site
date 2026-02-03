<?php

namespace App\Repositories;

use App\Models\Cms\CmsPage;
use App\PageStatus;
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
        $template = match ($pageType) {
            2 => 'news',
            3 => 'document',
            default => null,
        };

        return CmsPage::query()
            ->where('page_of_type', $pageType)
            ->where('page_status', PageStatus::PUBLISHED->value)
            ->when(
                $template,
                fn ($query) => $query->where('template', $template),
            )
            ->orderByRaw('CASE WHEN images IS NULL OR json_array_length(images) = 0 THEN 1 ELSE 0 END')
            ->orderByDesc('publication_date')
            ->limit($limit)
            ->get();
    }

    public function search(string $term, int $limit = 10): Collection
    {
        return CmsPage::query()
            ->where('page_status', PageStatus::PUBLISHED->value)
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

        if ($page->page_status !== PageStatus::PUBLISHED) {
            throw new ModelNotFoundException('Page not published');
        }

        return $page;
    }
}
