<?php

namespace Tests\Feature;

use App\Models\Cms\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_version_is_accessible_for_published_news(): void
    {
        CmsPage::factory()->create([
            'title' => 'Новость',
            'url' => '/news/demo',
            'page_status' => 3,
            'page_of_type' => 2,
            'content' => '<p>Тестовый контент</p>',
            'images' => [
                'cms/news/images/photo.jpg',
            ],
        ]);

        $this->get('/print/news/demo')
            ->assertStatus(200)
            ->assertSee('Новость')
            ->assertSee('/legacy/image/top1.gif')
            ->assertSee('<p>Тестовый контент</p>', false)
            ->assertSee('/storage/cms/news/images/photo.jpg');
    }

    public function test_print_version_is_accessible_for_published_document(): void
    {
        CmsPage::factory()->create([
            'title' => 'Документ',
            'url' => '/documents/demo',
            'page_status' => 3,
            'page_of_type' => 3,
            'content' => '<p>Тестовый контент</p>',
            'path' => 'cms/documents/attachments/doc.pdf',
        ]);

        $this->get('/print/documents/demo')
            ->assertStatus(200)
            ->assertSee('Документ')
            ->assertSee('/legacy/image/top1.gif')
            ->assertSee('<p>Тестовый контент</p>', false)
            ->assertSee('/storage/cms/documents/attachments/doc.pdf');
    }

    public function test_print_version_is_not_accessible_for_draft_news_for_guest(): void
    {
        CmsPage::factory()->create([
            'title' => 'Черновик',
            'url' => '/news/draft',
            'page_status' => 1,
            'page_of_type' => 2,
        ]);

        $this->get('/print/news/draft')->assertNotFound();
    }

    public function test_print_version_is_not_accessible_for_non_news_and_non_document_pages(): void
    {
        CmsPage::factory()->create([
            'title' => 'Обычная страница',
            'url' => '/about',
            'page_status' => 3,
            'page_of_type' => 1,
        ]);

        $this->get('/print/about')->assertNotFound();
    }
}
