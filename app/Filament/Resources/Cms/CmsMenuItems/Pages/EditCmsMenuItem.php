<?php

namespace App\Filament\Resources\Cms\CmsMenuItems\Pages;

use App\Filament\Resources\Cms\CmsMenuItems\CmsMenuItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsMenuItem extends EditRecord
{
    protected static string $resource = CmsMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
