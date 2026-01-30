<?php

namespace App\Filament\Resources\Cms\VirtualReceptions\Pages;

use App\Filament\Resources\Cms\VirtualReceptions\VirtualReceptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVirtualReception extends EditRecord
{
    protected static string $resource = VirtualReceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
