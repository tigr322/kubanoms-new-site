<?php

namespace App\Filament\Resources\Cms\CmsSettings;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerSettingHelper
{
    public static function isBanner(?string $name): bool
    {
        return filled($name) && Str::contains(Str::upper($name), 'BANNERS');
    }

    public static function directory(?string $name): string
    {
        $upper = Str::upper((string) $name);

        return match (true) {
            Str::contains($upper, 'LEFT') => 'cms/banners/left',
            Str::contains($upper, 'RIGHT') => 'cms/banners/right',
            default => 'cms/banners',
        };
    }

    public static function buildHtml(array $banners): string
    {
        return collect($banners)
            ->filter(fn ($item) => filled($item['image'] ?? null))
            ->map(function ($item): string {
                $src = self::toPublicUrl($item['image']);
                $alt = e($item['alt'] ?? 'Баннер');
                $img = '<img src="'.$src.'" alt="'.$alt.'" />';

                if (filled($item['url'] ?? null)) {
                    return '<a href="'.e($item['url']).'" target="_blank" rel="noopener">'.$img.'</a>';
                }

                return $img;
            })
            ->implode(PHP_EOL);
    }

    public static function extractFromContent(?string $content): array
    {
        if (! $content) {
            return [];
        }

        $dom = new DOMDocument;
        $contentWithEncoding = '<?xml encoding="utf-8" ?>'.$content;

        libxml_use_internal_errors(true);
        $dom->loadHTML($contentWithEncoding);
        libxml_clear_errors();

        $items = [];

        foreach ($dom->getElementsByTagName('img') as $image) {
            $src = $image->getAttribute('src');

            if (! $src) {
                continue;
            }

            $relative = self::toRelativePath($src);

            if (! $relative) {
                continue;
            }

            $items[] = [
                'image' => $relative,
                'url' => $image->parentNode instanceof DOMElement && $image->parentNode->tagName === 'a'
                    ? ($image->parentNode->getAttribute('href') ?: null)
                    : null,
                'alt' => $image->getAttribute('alt') ?: null,
            ];
        }

        return $items;
    }

    public static function normalizeContent(?string $content): ?string
    {
        if (! $content) {
            return $content;
        }

        return preg_replace_callback('/(<img[^>]+src=["\'])([^"\']+)(["\'])/i', function ($matches): string {
            $prefix = $matches[1];
            $src = $matches[2];
            $suffix = $matches[3];

            $relative = self::toRelativePath($src);

            if (! $relative) {
                return $matches[0];
            }

            return $prefix.self::toPublicUrl($relative).$suffix;
        }, $content);
    }

    private static function toRelativePath(string $src): ?string
    {
        $publicPrefix = rtrim(Storage::disk('public')->url('/'), '/');

        if (Str::startsWith($src, $publicPrefix)) {
            return ltrim(Str::after($src, $publicPrefix), '/');
        }

        if (Str::startsWith($src, '/storage/')) {
            return ltrim(Str::after($src, '/storage/'), '/');
        }

        if (Str::startsWith($src, 'storage/')) {
            return ltrim(Str::after($src, 'storage/'), '/');
        }

        if (Str::startsWith($src, 'cms/')) {
            return $src;
        }

        return null;
    }

    private static function toPublicUrl(string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (preg_match('#https?://[^/]+(/.*)$#', $url, $matches)) {
            return $matches[1];
        }

        return $url;
    }
}
