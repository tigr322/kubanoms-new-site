<?php

namespace App\Support\Cms;

use Illuminate\Support\Str;

class ContentStorageLinkPresenter
{
    public function buildPlainText(mixed $images, mixed $attachments, ?string $content): string
    {
        $links = $this->collect($images, $attachments, $content);

        if ($links === []) {
            return 'Ссылки появятся после загрузки файлов в "Фотографии"/"Документы" и сохранения страницы.';
        }

        return implode(PHP_EOL, $links);
    }

    public function buildHtmlSnippets(mixed $images, mixed $attachments, ?string $content): string
    {
        $links = $this->collect($images, $attachments, $content);

        if ($links === []) {
            return 'Сначала загрузите файлы и сохраните страницу.';
        }

        $snippets = array_map(function (string $link): string {
            $filename = basename((string) parse_url($link, PHP_URL_PATH));
            $extension = Str::lower((string) pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'], true)) {
                return '<img src="'.$link.'" alt="" />';
            }

            return '<a href="'.$link.'" target="_blank" rel="noopener">'.$filename.'</a>';
        }, $links);

        return implode(PHP_EOL, $snippets);
    }

    public function buildExternalVideoPlainText(?string $content): string
    {
        $links = $this->collectExternalVideoLinks($content);

        if ($links === []) {
            return 'Внешние mp4-ссылки не найдены.';
        }

        return implode(PHP_EOL, $links);
    }

    /**
     * @param  iterable<int, array<string, mixed>|object>  $files
     */
    public function buildCmsFilesTableHtml(iterable $files): string
    {
        $rows = [];

        foreach ($files as $file) {
            $id = $this->escape((string) $this->readField($file, 'id'));
            $name = $this->escape((string) $this->readField($file, 'original_name'));
            $path = $this->escape((string) $this->readField($file, 'path'));
            $storageUrl = $this->escape((string) $this->readField($file, 'storage_url'));
            $mimeType = $this->escape((string) $this->readField($file, 'mime_type'));
            $extension = $this->escape((string) $this->readField($file, 'extension'));
            $updatedAt = $this->escape((string) $this->readField($file, 'update_date'));
            $searchIndex = $this->escape(implode(' ', [
                (string) $this->readField($file, 'id'),
                (string) $this->readField($file, 'original_name'),
                (string) $this->readField($file, 'path'),
                (string) $this->readField($file, 'storage_url'),
                (string) $this->readField($file, 'mime_type'),
                (string) $this->readField($file, 'extension'),
                (string) $this->readField($file, 'update_date'),
            ]));

            $rows[] = '<tr data-search-index="'.$searchIndex.'">
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">'.$id.'</td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">'.$name.'</td>
                <td style="padding:8px;border-bottom:1px solid var(--border);font-family:monospace;color:var(--foreground);background:var(--background);">'.$path.'</td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">
                    <input type="text" readonly value="'.$storageUrl.'" style="width:100%;font-family:monospace;font-size:12px;padding:6px;border:1px solid var(--border);border-radius:4px;background:var(--background);color:var(--foreground);" />
                </td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);white-space:nowrap;">
                    <button
                        type="button"
                        data-copy-storage-url="'.$storageUrl.'"
                        onclick="(async function(button){const text = button.getAttribute(\'data-copy-storage-url\') || \'\'; if (!text) { return; } const original = button.innerText; try { if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(text); } else { const area = document.createElement(\'textarea\'); area.value = text; area.style.position = \'fixed\'; area.style.left = \'-9999px\'; document.body.appendChild(area); area.focus(); area.select(); document.execCommand(\'copy\'); area.remove(); } button.innerText = \'Скопировано\'; } catch (error) { button.innerText = \'Ошибка\'; } setTimeout(function(){ button.innerText = original; }, 1200); })(this);"
                        style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;background:var(--muted);color:var(--foreground);font-size:12px;cursor:pointer;"
                    >
                        Копировать
                    </button>
                </td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">'.$mimeType.'</td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">'.$extension.'</td>
                <td style="padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--background);">'.$updatedAt.'</td>
            </tr>';
        }

        if ($rows === []) {
            return 'Файлы не найдены.';
        }

        return '<div data-cms-files-block="1" style="display:grid;gap:8px;">
            <input
                type="text"
                placeholder="Быстрый поиск: ID, имя, path, URL, mime, ext..."
                oninput="(function(input){ var root = input.closest(\'[data-cms-files-block]\'); if (!root) { return; } var query = (input.value || \'\').toLowerCase().trim(); var rows = root.querySelectorAll(\'tbody tr[data-search-index]\'); rows.forEach(function(row){ var text = (row.getAttribute(\'data-search-index\') || \'\').toLowerCase(); row.style.display = (query === \'\' || text.indexOf(query) !== -1) ? \'\' : \'none\'; }); })(this);"
                style="width:100%;font-size:12px;padding:8px;border:1px solid var(--border);border-radius:8px;background:var(--background);color:var(--foreground);"
            />
            <div style="max-height:360px;overflow:auto;border:1px solid var(--border);border-radius:8px;background:var(--background);">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead style="position:sticky;top:0;background:var(--muted);color:var(--foreground);z-index:1;">
                    <tr>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">ID</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Имя</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Path</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Storage URL</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Копировать</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">MIME</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Ext</th>
                        <th style="text-align:left;padding:8px;border-bottom:1px solid var(--border);color:var(--foreground);background:var(--muted);">Update</th>
                    </tr>
                </thead>
                <tbody>'.implode('', $rows).'</tbody>
            </table>
            </div>
        </div>';
    }

    /**
     * @return array<int, string>
     */
    public function collect(mixed $images, mixed $attachments, ?string $content): array
    {
        $links = [];

        foreach ($this->toArray($images) as $value) {
            $normalized = $this->normalizeStoragePath((string) $value, true);

            if ($normalized !== null) {
                $links[] = $normalized;
            }
        }

        foreach ($this->toArray($attachments) as $value) {
            $normalized = $this->normalizeStoragePath((string) $value, true);

            if ($normalized !== null) {
                $links[] = $normalized;
            }
        }

        if (is_string($content) && $content !== '') {
            preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $content, $matches);

            foreach ($matches[1] ?? [] as $candidate) {
                $normalized = $this->normalizeStoragePath((string) $candidate, false);

                if ($normalized !== null) {
                    $links[] = $normalized;
                }
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * @return array<int, string>
     */
    public function collectExternalVideoLinks(?string $content): array
    {
        if (! is_string($content) || $content === '') {
            return [];
        }

        preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $content, $matches);

        $links = [];

        foreach ($matches[1] ?? [] as $candidate) {
            $value = trim((string) $candidate);

            if ($value === '' || ! $this->isExternalUrl($value)) {
                continue;
            }

            if ($this->normalizeStoragePath($value, false) !== null) {
                continue;
            }

            $path = parse_url($value, PHP_URL_PATH);

            if (! is_string($path) || $path === '') {
                continue;
            }

            if (Str::lower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'mp4') {
                continue;
            }

            $links[] = $value;
        }

        return array_values(array_unique($links));
    }

    private function isExternalUrl(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://', '//']);
    }

    private function normalizeStoragePath(string $path, bool $allowRelativeStorage): ?string
    {
        $value = trim($path);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, '/storage/')) {
            return '/'.ltrim($value, '/');
        }

        if (Str::startsWith($value, 'storage/')) {
            return '/'.ltrim($value, '/');
        }

        if (Str::startsWith($value, 'public/')) {
            return '/storage/'.ltrim(Str::after($value, 'public/'), '/');
        }

        if ($allowRelativeStorage && ! Str::contains($value, '://') && Str::startsWith($value, 'cms/')) {
            return '/storage/'.ltrim($value, '/');
        }

        $urlPath = parse_url($value, PHP_URL_PATH);

        if (! is_string($urlPath) || $urlPath === '') {
            return null;
        }

        if (Str::startsWith($urlPath, '/storage/')) {
            return '/'.ltrim($urlPath, '/');
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function toArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function (mixed $item): ?string {
            if (! is_string($item)) {
                return null;
            }

            $trimmed = trim($item);

            return $trimmed === '' ? null : $trimmed;
        }, $value)));
    }

    /**
     * @param  array<string, mixed>|object  $value
     */
    private function readField(array|object $value, string $field): mixed
    {
        if (is_array($value)) {
            return $value[$field] ?? '';
        }

        if (! isset($value->{$field})) {
            return '';
        }

        return $value->{$field};
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
