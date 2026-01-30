<?php

namespace App\Filament\Resources\Cms\CmsSettings\Pages;

use App\Filament\Resources\Cms\CmsSettings\CmsSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsSetting extends EditRecord
{
    protected static string $resource = CmsSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
