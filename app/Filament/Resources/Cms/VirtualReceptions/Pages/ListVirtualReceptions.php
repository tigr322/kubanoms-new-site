<?php

namespace App\Filament\Resources\Cms\VirtualReceptions\Pages;

use App\Filament\Resources\Cms\VirtualReceptions\VirtualReceptionResource;
use Filament\Resources\Pages\ListRecords;

class ListVirtualReceptions extends ListRecords
{
    protected static string $resource = VirtualReceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Убираем кнопку создания, так как это только для просмотра
        ];
    }

    public function getTitle(): string
    {
        return 'Виртуальная приемная';
    }
}
