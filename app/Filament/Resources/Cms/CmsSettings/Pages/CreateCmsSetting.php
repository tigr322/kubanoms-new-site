<?php

namespace App\Filament\Resources\Cms\CmsSettings\Pages;

use App\Filament\Resources\Cms\CmsSettings\CmsSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsSetting extends CreateRecord
{
    protected static string $resource = CmsSettingResource::class;
}
