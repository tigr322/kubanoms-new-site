<?php

namespace Tests\Feature;

use Tests\TestCase;

class UploadConfigurationTest extends TestCase
{
    public function test_default_upload_limits_are_set_for_livewire_and_cms(): void
    {
        $this->assertSame(
            ['required', 'file', 'max:1012000'],
            config('livewire.temporary_file_upload.rules'),
        );
        $this->assertSame(30, config('livewire.temporary_file_upload.max_upload_time'));
        $this->assertSame(25, config('livewire.payload.max_nesting_depth'));
        $this->assertSame(1012000, config('cms.file_upload_max_kb'));
    }

    public function test_upload_limits_can_be_overridden_by_environment_variables(): void
    {
        putenv('LIVEWIRE_UPLOAD_MAX_KB=2048');
        putenv('LIVEWIRE_UPLOAD_MAX_MINUTES=12');
        putenv('LIVEWIRE_PAYLOAD_MAX_NESTING_DEPTH=40');
        putenv('CMS_FILE_UPLOAD_MAX_KB=1024');

        $livewireConfig = require config_path('livewire.php');
        $cmsConfig = require config_path('cms.php');

        $this->assertSame(['required', 'file', 'max:2048'], $livewireConfig['temporary_file_upload']['rules']);
        $this->assertSame(12, $livewireConfig['temporary_file_upload']['max_upload_time']);
        $this->assertSame(40, $livewireConfig['payload']['max_nesting_depth']);
        $this->assertSame(1024, $cmsConfig['file_upload_max_kb']);

        putenv('LIVEWIRE_UPLOAD_MAX_KB');
        putenv('LIVEWIRE_UPLOAD_MAX_MINUTES');
        putenv('LIVEWIRE_PAYLOAD_MAX_NESTING_DEPTH');
        putenv('CMS_FILE_UPLOAD_MAX_KB');
    }

    public function test_file_folder_relation_manager_uses_configured_upload_limit(): void
    {
        $source = file_get_contents(app_path('Filament/Resources/Cms/CmsFileFolders/RelationManagers/FilesRelationManager.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "->maxSize((int) config('cms.file_upload_max_kb', 1012000))",
            $source,
        );
    }
}
