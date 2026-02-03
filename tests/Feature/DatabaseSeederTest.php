<?php

namespace Tests\Feature;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_creates_nested_menu_items(): void
    {
        $this->seed();

        $menuCount = CmsMenu::query()->count();
        $menuItemCount = CmsMenuItem::query()->count();
        $pageCount = CmsPage::query()->count();
        $settingCount = CmsSetting::query()->count();

        $this->seed();

        $this->assertSame($menuCount, CmsMenu::query()->count());
        $this->assertSame($menuItemCount, CmsMenuItem::query()->count());
        $this->assertSame($pageCount, CmsPage::query()->count());
        $this->assertSame($settingCount, CmsSetting::query()->count());

        $navbar = CmsMenu::query()
            ->where('name', 'NAVBAR')
            ->firstOrFail();

        $citizens = CmsMenuItem::query()
            ->where('menu_id', $navbar->id)
            ->whereNull('parent_id')
            ->where('title', 'Гражданам')
            ->firstOrFail();

        $this->assertDatabaseHas('cms_menu_item', [
            'menu_id' => $navbar->id,
            'parent_id' => $citizens->id,
            'title' => 'Получение полиса ОМС',
        ]);
    }
}
