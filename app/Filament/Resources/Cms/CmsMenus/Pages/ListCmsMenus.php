<?php

namespace App\Filament\Resources\Cms\CmsMenus\Pages;

use App\Filament\Resources\Cms\CmsMenus\CmsMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsMenus extends ListRecords
{
    protected static string $resource = CmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
