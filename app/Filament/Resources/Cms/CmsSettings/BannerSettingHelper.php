<?php

namespace App\Filament\Resources\Cms\CmsSettings;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerSettingHelper
{
    private const TYPE_IMAGE = 'image';

    private const TYPE_HTML = 'html';

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
        return collect(self::normalizeBanners($banners))
            ->map(function (array $item): string {
                if (($item['type'] ?? self::TYPE_IMAGE) === self::TYPE_HTML) {
                    return self::normalizeContent($item['html'] ?? '') ?? '';
                }

                $src = self::toPublicUrl($item['image']);
                $alt = e($item['alt'] ?? 'Баннер');
                $style = self::buildImageStyle($item);
                $img = '<img src="'.$src.'" alt="'.$alt.'"'.$style.' />';

                if (! filled($item['url'] ?? null)) {
                    return $img;
                }

                $target = ($item['open_in_new_tab'] ?? true) ? ' target="_blank" rel="noopener"' : '';

                return '<a href="'.e($item['url']).'"'.$target.'>'.$img.'</a>';
            })
            ->filter()
            ->implode(PHP_EOL);
    }

    /**
     * @param  array<int, array<string, mixed>>  $banners
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeBanners(array $banners): array
    {
        return collect($banners)
            ->filter(fn (array $item): bool => filled($item['type'] ?? null) || filled($item['image'] ?? null) || filled($item['html'] ?? null))
            ->map(function (array $item): ?array {
                $type = $item['type'] ?? self::TYPE_IMAGE;

                if ($type === self::TYPE_HTML) {
                    $html = trim((string) ($item['html'] ?? ''));

                    if ($html === '') {
                        return null;
                    }

                    return [
                        'type' => self::TYPE_HTML,
                        'html' => $html,
                    ];
                }

                $image = $item['image'] ?? null;

                if (is_array($image)) {
                    $image = $image[0] ?? null;
                }

                if (! $image) {
                    return null;
                }

                $relative = self::toRelativePath((string) $image);

                return [
                    'type' => self::TYPE_IMAGE,
                    'image' => $relative ?? $image,
                    'url' => filled($item['url'] ?? null) ? $item['url'] : null,
                    'alt' => filled($item['alt'] ?? null) ? $item['alt'] : null,
                    'width' => self::normalizeDimension($item['width'] ?? null),
                    'height' => self::normalizeDimension($item['height'] ?? null),
                    'open_in_new_tab' => $item['open_in_new_tab'] ?? true,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function decodeContent(?string $content): array
    {
        if (! filled($content)) {
            return [];
        }

        $trimmed = trim($content);

        if (self::isJsonContent($trimmed)) {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded['banners'] ?? $decoded;

                if (is_array($payload)) {
                    return self::normalizeBanners($payload);
                }
            }
        }

        return self::extractFromContent($content);
    }

    /**
     * @param  array<int, array<string, mixed>>  $banners
     */
    public static function encodeContent(array $banners): string
    {
        return json_encode(
            self::normalizeBanners($banners),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '[]';
    }

    public static function renderContent(?string $content): ?string
    {
        if (! filled($content)) {
            return $content;
        }

        $trimmed = trim($content);

        if (self::isJsonContent($trimmed)) {
            return self::buildHtml(self::decodeContent($trimmed));
        }

        return self::normalizeContent($content);
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
                'type' => self::TYPE_IMAGE,
                'image' => $relative,
                'url' => $image->parentNode instanceof DOMElement && $image->parentNode->tagName === 'a'
                    ? ($image->parentNode->getAttribute('href') ?: null)
                    : null,
                'alt' => $image->getAttribute('alt') ?: null,
                'width' => self::extractDimension($image, 'width'),
                'height' => self::extractDimension($image, 'height'),
                'open_in_new_tab' => $image->parentNode instanceof DOMElement
                    && $image->parentNode->tagName === 'a'
                    && $image->parentNode->getAttribute('target') !== '_self',
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

    private static function isJsonContent(string $content): bool
    {
        if (! str_starts_with($content, '[') && ! str_starts_with($content, '{')) {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function buildImageStyle(array $item): string
    {
        $styles = [];

        $width = self::normalizeDimension($item['width'] ?? null);
        $height = self::normalizeDimension($item['height'] ?? null);

        if ($width) {
            $styles[] = 'width: '.$width;
        }

        if ($height) {
            $styles[] = 'height: '.$height;
        }

        if ($styles === []) {
            return '';
        }

        return ' style="'.e(implode('; ', $styles).';').'"';
    }

    private static function normalizeDimension(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return $value.'px';
        }

        if (! is_string($value)) {
            return null;
        }

        $dimension = trim($value);

        if ($dimension === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $dimension)) {
            return $dimension.'px';
        }

        if (preg_match('/^\d+(\.\d+)?(px|%|em|rem|vh|vw)$/i', $dimension)) {
            return $dimension;
        }

        return null;
    }

    private static function extractDimension(DOMElement $image, string $property): ?string
    {
        $style = (string) $image->getAttribute('style');

        if ($style !== '' && preg_match('/(?:^|;)\s*'.preg_quote($property, '/').'\s*:\s*([^;]+)/i', $style, $matches)) {
            return self::normalizeDimension($matches[1]);
        }

        return self::normalizeDimension($image->getAttribute($property) ?: null);
    }
}
