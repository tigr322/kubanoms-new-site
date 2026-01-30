<?php

namespace App\Filament\Resources\Cms\CmsFileFolders\Pages;

use App\Filament\Resources\Cms\CmsFileFolders\CmsFileFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsFileFolders extends ListRecords
{
    protected static string $resource = CmsFileFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
