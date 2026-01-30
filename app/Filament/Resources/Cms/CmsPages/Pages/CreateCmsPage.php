<?php

namespace App\Filament\Resources\Cms\CmsPages\Pages;

use App\Filament\Resources\Cms\CmsPages\CmsPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;
}
