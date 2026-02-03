<?php

namespace Tests\Feature;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Repositories\MenuRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_root_items_and_nests_children(): void
    {
        $menu = CmsMenu::query()->create([
            'name' => 'NAVBAR',
            'title' => 'Navbar',
            'max_depth' => 2,
        ]);

        $rootSecond = CmsMenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Root 2',
            'sort_order' => 2,
            'visible' => true,
        ]);

        $rootFirst = CmsMenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Root 1',
            'sort_order' => 1,
            'visible' => true,
        ]);

        $child = CmsMenuItem::query()->create([
            'parent_id' => $rootFirst->id,
            'title' => 'Child 1',
            'sort_order' => 1,
            'visible' => true,
        ]);

        $items = app(MenuRepository::class)->getVisibleItems('NAVBAR');

        $this->assertCount(2, $items);
        $this->assertSame($rootFirst->id, $items->first()->id);
        $this->assertSame($rootSecond->id, $items->last()->id);

        $this->assertCount(1, $items->first()->children);
        $this->assertSame($child->id, $items->first()->children->first()->id);

        // Menu id should be inherited from parent.
        $this->assertSame($menu->id, $child->fresh()->menu_id);
    }
}
