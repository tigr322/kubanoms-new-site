<?php

namespace App\Filament\Resources\Cms\CmsFiles\Pages;

use App\Filament\Resources\Cms\CmsFiles\CmsFileResource;
use Filament\Actions\DeleteAction;
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
}
