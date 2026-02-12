<?php

namespace App\Filament\Resources\Cms\CmsPages\Pages;

use App\Filament\Resources\Cms\CmsPages\CmsPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawHtml = $data['content_raw_html'] ?? null;
        unset($data['content_raw_html']);

        if (is_string($rawHtml) && trim($rawHtml) !== '') {
            $data['content'] = $rawHtml;
        }

        return $data;
    }
}
