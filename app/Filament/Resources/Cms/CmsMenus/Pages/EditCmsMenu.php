<?php

namespace App\Filament\Resources\Cms\CmsMenus\Pages;

use App\Filament\Resources\Cms\CmsMenus\CmsMenuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsMenu extends EditRecord
{
    protected static string $resource = CmsMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
