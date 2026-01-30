<?php

namespace App\Repositories;

use App\Filament\Resources\Cms\CmsSettings\BannerSettingHelper;
use App\Models\Cms\CmsSetting;

class SettingRepository
{
    public function getContentByName(string $name): ?string
    {
        return CmsSetting::query()
            ->where('name', $name)
            ->where('visibility', true)
            ->value('content');
    }

    /**
     * Fetch multiple settings at once. Returns associative array name => content (only visible ones).
     */
    public function getMany(array $names): array
    {
        return CmsSetting::query()
            ->whereIn('name', $names)
            ->where('visibility', true)
            ->get(['name', 'content'])
            ->mapWithKeys(function (CmsSetting $setting): array {
                $content = $setting->content;

                if (BannerSettingHelper::isBanner($setting->name)) {
                    $content = BannerSettingHelper::normalizeContent($content);
                }

                return [$setting->name => $content];
            })
            ->all();
    }
}
