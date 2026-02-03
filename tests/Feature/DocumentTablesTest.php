<?php

namespace Tests\Feature;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsPageDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_page_renders_documents_table_props(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cms/documents/files/test.pdf', 'pdf');

        $page = CmsPage::factory()->create([
            'title' => 'Документы',
            'url' => '/documents/test.html',
            'page_status' => 3,
            'page_of_type' => 3,
            'template' => 'document',
        ]);

        $file = CmsFile::query()->create([
            'original_name' => 'test.pdf',
            'path' => 'cms/documents/files/test.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'description' => 'Test',
            'create_date' => now(),
            'create_user' => 'system',
        ]);

        CmsPageDocument::query()->create([
            'page_id' => $page->id,
            'file_id' => $file->id,
            'title' => 'Тестовый документ',
            'group_title' => '2026 год',
            'document_date' => '2026-02-01',
            'order' => 1,
            'is_visible' => true,
        ]);

        $this->get('/documents/test.html')->assertStatus(200)->assertInertia(
            fn (Assert $inertia) => $inertia
                ->component('DocumentDetail')
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Тестовый документ')
                ->where('documents.data.0.document_date', '01.02.2026')
                ->where('documents.data.0.file.url', Storage::disk('public')->url('cms/documents/files/test.pdf'))
                ->where('active_group', '2026 год'),
        );
    }
}
