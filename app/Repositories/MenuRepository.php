<?php

namespace App\Repositories;

use App\Models\Cms\CmsMenu;
use Illuminate\Support\Collection;

class MenuRepository
{
    public function findByName(string $name): ?CmsMenu
    {
        return CmsMenu::query()->where('name', $name)->first();
    }

    public function getVisibleItems(string $name): Collection
    {
        $menu = $this->findByName($name);

        if (! $menu) {
            return collect();
        }

        $maxDepth = max(1, (int) ($menu->max_depth ?? 1));

        return $menu->items()
            ->where('visible', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with([
                'page',
                ...$this->childrenEagerLoads($maxDepth - 1),
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function childrenEagerLoads(int $remainingDepth): array
    {
        if ($remainingDepth <= 0) {
            return [];
        }

        return [
            'children' => function ($query) use ($remainingDepth): void {
                $query
                    ->where('visible', true)
                    ->orderBy('sort_order')
                    ->with([
                        'page',
                        ...$this->childrenEagerLoads($remainingDepth - 1),
                    ]);
            },
        ];
    }
}
