<?php

namespace Tests\Feature;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchMenuItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_can_find_links_from_site_menu(): void
    {
        $menu = CmsMenu::query()->create([
            'name' => 'NAVBAR',
            'title' => 'Горизонтальное меню',
            'max_depth' => 2,
        ]);

        $item = CmsMenuItem::query()->create([
            'menu_id' => $menu->id,
            'title' => 'Филиалы',
            'url' => '/branches',
            'sort_order' => 1,
            'visible' => true,
        ]);

        $this->get('/search?q=%D0%A4%D0%B8%D0%BB%D0%B8%D0%B0%D0%BB%D1%8B')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('Search')
                ->has('results', 1)
                ->where('results.0.id', 'menu-'.$item->id)
                ->where('results.0.title', 'Филиалы')
                ->where('results.0.url', '/branches'),
        );
    }
}
