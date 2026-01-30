<?php

namespace App\Filament\Resources\Cms\VirtualReceptions\Pages;

use App\Filament\Resources\Cms\VirtualReceptions\VirtualReceptionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVirtualReception extends ViewRecord
{
    protected static string $resource = VirtualReceptionResource::class;

    protected static ?string $title = 'Просмотр обращения';

    protected static ?string $breadcrumb = 'Просмотр';

    protected function getHeaderActions(): array
    {
        return [
            // Убираем действия для просмотра
        ];
    }
}
