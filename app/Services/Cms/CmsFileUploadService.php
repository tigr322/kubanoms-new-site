<?php

namespace App\Services\Cms;

use App\Models\Cms\CmsFileFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CmsFileUploadService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeForSave(
        array $data,
        ?int $folderId = null,
        ?string $actor = null,
        bool $isCreate = true,
    ): array {
        $upload = $data['upload'] ?? null;
        unset($data['upload']);

        $resolvedFolderId = $this->resolveFolderId($folderId, $data['file_folder_id'] ?? null);

        if ($resolvedFolderId !== null) {
            $data['file_folder_id'] = $resolvedFolderId;
        }

        if ($upload instanceof UploadedFile) {
            $directory = $this->uploadDirectory($resolvedFolderId);
            $path = $upload->store($directory, 'public');

            $data['path'] = $path;
            $data['original_name'] = $this->normalizeOriginalName(
                value: $data['original_name'] ?? null,
                fallback: $upload->getClientOriginalName(),
                path: $path,
            );
            $data['mime_type'] = $this->normalizeMimeType(
                value: $data['mime_type'] ?? null,
                fallback: (string) $upload->getMimeType(),
            );
            $data['extension'] = $this->normalizeExtension(
                value: $data['extension'] ?? null,
                fallback: $upload->getClientOriginalExtension(),
                path: $path,
            );
        } else {
            $path = (string) ($data['path'] ?? '');

            $data['original_name'] = $this->normalizeOriginalName(
                value: $data['original_name'] ?? null,
                fallback: '',
                path: $path,
            );
            $data['mime_type'] = $this->normalizeMimeType(
                value: $data['mime_type'] ?? null,
                fallback: '',
            );
            $data['extension'] = $this->normalizeExtension(
                value: $data['extension'] ?? null,
                fallback: '',
                path: $path,
            );
        }

        $currentActor = trim((string) $actor);

        if ($currentActor === '') {
            $currentActor = 'filament:admin';
        }

        if ($isCreate && blank($data['create_date'] ?? null)) {
            $data['create_date'] = now();
        }

        if ($isCreate && blank($data['create_user'] ?? null)) {
            $data['create_user'] = $currentActor;
        }

        $data['update_date'] = now();
        $data['update_user'] = $currentActor;

        return $data;
    }

    private function resolveFolderId(?int $folderId, mixed $dataFolderId): ?int
    {
        if ($folderId !== null && $folderId > 0) {
            return $folderId;
        }

        if (is_numeric($dataFolderId)) {
            $candidate = (int) $dataFolderId;

            return $candidate > 0 ? $candidate : null;
        }

        return null;
    }

    private function uploadDirectory(?int $folderId): string
    {
        if (! $folderId) {
            return 'cms/files';
        }

        $folder = CmsFileFolder::query()->find($folderId);

        if (! $folder) {
            return 'cms/files';
        }

        $seed = trim((string) ($folder->name ?: $folder->title));
        $slug = Str::slug($seed !== '' ? $seed : (string) $folder->id);

        return 'cms/files/'.$slug;
    }

    private function normalizeOriginalName(mixed $value, string $fallback, string $path): string
    {
        $normalized = trim((string) $value);

        if ($normalized !== '') {
            return $normalized;
        }

        $fallback = trim($fallback);

        if ($fallback !== '') {
            return $fallback;
        }

        return basename($path);
    }

    private function normalizeMimeType(mixed $value, string $fallback): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            $normalized = trim($fallback);
        }

        if ($normalized === '') {
            $normalized = 'application/octet-stream';
        }

        return substr($normalized, 0, 25);
    }

    private function normalizeExtension(mixed $value, string $fallback, string $path): string
    {
        $normalized = trim(strtolower((string) $value));

        if ($normalized === '') {
            $normalized = trim(strtolower($fallback));
        }

        if ($normalized === '' && $path !== '') {
            $normalized = trim(strtolower((string) pathinfo($path, PATHINFO_EXTENSION)));
        }

        if ($normalized === '') {
            $normalized = 'bin';
        }

        return $normalized;
    }
}
