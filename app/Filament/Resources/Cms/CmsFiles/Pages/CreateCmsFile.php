<?php

namespace App\Filament\Resources\Cms\CmsFiles\Pages;

use App\Filament\Resources\Cms\CmsFiles\CmsFileResource;
use App\Services\Cms\CmsFileUploadService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsFile extends CreateRecord
{
    protected static string $resource = CmsFileResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(CmsFileUploadService::class)->normalizeForSave(
            data: $data,
            folderId: null,
            actor: Filament::auth()->user()?->name,
            isCreate: true,
        );
    }
}
