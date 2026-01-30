<?php

namespace App\Filament\Resources\Cms\CmsFiles\Pages;

use App\Filament\Resources\Cms\CmsFiles\CmsFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsFiles extends ListRecords
{
    protected static string $resource = CmsFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
