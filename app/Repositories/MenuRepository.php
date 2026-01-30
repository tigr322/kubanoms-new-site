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

        return $menu->items()
            ->with(['children' => fn ($query) => $query->with('children')])
            ->where('visible', true)
            ->get();
    }
}
