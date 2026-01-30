<?php

namespace App\Filament\Resources\Cms\CmsFiles\Pages;

use App\Filament\Resources\Cms\CmsFiles\CmsFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsFile extends CreateRecord
{
    protected static string $resource = CmsFileResource::class;
}
