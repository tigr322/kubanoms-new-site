<?php

namespace App\Filament\Resources\Cms\CmsMenuItems\Pages;

use App\Filament\Resources\Cms\CmsMenuItems\CmsMenuItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsMenuItems extends ListRecords
{
    protected static string $resource = CmsMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
