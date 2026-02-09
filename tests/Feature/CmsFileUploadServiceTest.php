<?php

namespace Tests\Feature;

use App\Models\Cms\CmsFileFolder;
use App\Services\Cms\CmsFileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsFileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_metadata_for_uploaded_file_and_stores_it_to_public_disk(): void
    {
        Storage::fake('public');

        $folder = CmsFileFolder::query()->create([
            'name' => 'Документы фонда',
            'title' => 'Документы фонда',
        ]);

        $service = app(CmsFileUploadService::class);
        $result = $service->normalizeForSave(
            data: [
                'upload' => UploadedFile::fake()->create('policy.pdf', 10, 'application/pdf'),
                'description' => 'Test document',
            ],
            folderId: $folder->id,
            actor: 'admin-user',
            isCreate: true,
        );

        $this->assertArrayHasKey('path', $result);
        $this->assertStringStartsWith('cms/files/dokumenty-fonda/', (string) $result['path']);
        $this->assertSame('policy.pdf', $result['original_name']);
        $this->assertSame('application/pdf', $result['mime_type']);
        $this->assertSame('pdf', $result['extension']);
        $this->assertSame($folder->id, $result['file_folder_id']);
        $this->assertSame('admin-user', $result['create_user']);
        $this->assertSame('admin-user', $result['update_user']);
        $this->assertNotNull($result['create_date']);
        $this->assertNotNull($result['update_date']);
        Storage::disk('public')->assertExists((string) $result['path']);
    }

    public function test_it_falls_back_to_default_values_when_file_is_not_uploaded(): void
    {
        $service = app(CmsFileUploadService::class);
        $result = $service->normalizeForSave(
            data: [
                'path' => 'cms/files/manual/fallback.bin',
                'original_name' => '',
                'mime_type' => '',
                'extension' => '',
            ],
            folderId: null,
            actor: '',
            isCreate: false,
        );

        $this->assertSame('fallback.bin', $result['original_name']);
        $this->assertSame('application/octet-stream', $result['mime_type']);
        $this->assertSame('bin', $result['extension']);
        $this->assertSame('filament:admin', $result['update_user']);
        $this->assertArrayNotHasKey('create_user', $result);
    }
}
