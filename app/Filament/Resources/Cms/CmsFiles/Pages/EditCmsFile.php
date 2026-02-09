<?php

namespace App\Filament\Resources\Cms\CmsFiles\Pages;

use App\Filament\Resources\Cms\CmsFiles\CmsFileResource;
use App\Services\Cms\CmsFileUploadService;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditCmsFile extends EditRecord
{
    protected static string $resource = CmsFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(CmsFileUploadService::class)->normalizeForSave(
            data: $data,
            folderId: null,
            actor: Filament::auth()->user()?->name,
            isCreate: false,
        );
    }
}
