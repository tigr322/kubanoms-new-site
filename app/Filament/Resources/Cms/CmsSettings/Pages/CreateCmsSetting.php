<?php

namespace App\Filament\Resources\Cms\CmsSettings\Pages;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use App\Filament\Resources\Cms\CmsSettings\CmsSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsSetting extends CreateRecord
{
    protected static string $resource = CmsSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateBannerData($data);
    }

    private function mutateBannerData(array $data): array
    {
        if (! BannerSettingHelper::isBanner($data['name'] ?? null)) {
            unset($data['banners']);

            return $data;
        }

        $data['content'] = BannerSettingHelper::encodeContent($data['banners'] ?? []);
        unset($data['banners']);

        return $data;
    }
}
