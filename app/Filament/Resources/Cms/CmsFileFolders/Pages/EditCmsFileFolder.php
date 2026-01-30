<?php

namespace App\Filament\Resources\Cms\CmsFileFolders\Pages;

use App\Filament\Resources\Cms\CmsFileFolders\CmsFileFolderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsFileFolder extends EditRecord
{
    protected static string $resource = CmsFileFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
