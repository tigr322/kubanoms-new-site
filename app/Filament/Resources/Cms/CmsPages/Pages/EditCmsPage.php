<?php

namespace App\Filament\Resources\Cms\CmsPages\Pages;

use App\Filament\Resources\Cms\CmsPages\CmsPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawHtml = $data['content_raw_html'] ?? null;
        unset($data['content_raw_html']);

        if (! is_string($rawHtml)) {
            return $data;
        }

        $recordContent = $this->record->getRawOriginal('content');
        $recordContent = is_string($recordContent) ? $recordContent : '';

        if (trim($rawHtml) !== '' && $rawHtml !== $recordContent) {
            $data['content'] = $rawHtml;
        }

        return $data;
    }
}
