<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        CmsPage::factory()->create([
            'title' => 'Главная',
            'url' => '/',
            'page_status' => 3,
            'page_of_type' => 1,
            'template' => 'home',
        ]);

        $this->get('/')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page->component('Home'),
        );
    }

    public function test_page_by_slug_is_accessible(): void
    {
        CmsPage::factory()->create([
            'title' => 'Гражданам',
            'url' => '/grazhd.html',
            'page_status' => 3,
            'page_of_type' => 1,
        ]);

        $this->get('/grazhd.html')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page->component('GenericPage'),
        );
    }

    public function test_draft_page_is_not_visible_for_guest(): void
    {
        CmsPage::factory()->create([
            'title' => 'Черновик',
            'url' => '/draft-page',
            'page_status' => 1,
            'page_of_type' => 1,
        ]);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_news_page_renders_media(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('cms/news/images/photo.jpg', 'img');
        Storage::disk('public')->put('cms/news/attachments/file.pdf', 'pdf');

        CmsPage::factory()->create([
            'title' => 'Новость',
            'url' => '/news/demo',
            'page_status' => 3,
            'page_of_type' => 2,
            'images' => ['cms/news/images/photo.jpg'],
            'attachments' => ['cms/news/attachments/file.pdf'],
        ]);

        $this->get('/news/demo')->assertStatus(200)->assertInertia(
            fn (Assert $page) => $page
                ->component('NewsDetail')
                ->where('page.images.0', Storage::disk('public')->url('cms/news/images/photo.jpg'))
                ->where('page.attachments.0.name', 'file.pdf')
                ->where('page.attachments.0.url', Storage::disk('public')->url('cms/news/attachments/file.pdf')),
        );
    }
}
