<?php

namespace App\Filament\Resources\Cms\CmsMenuItems\Pages;

use App\Filament\Resources\Cms\CmsMenuItems\CmsMenuItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsMenuItem extends CreateRecord
{
    protected static string $resource = CmsMenuItemResource::class;
}
