<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $original_name
 * @property string $path
 * @property string|null $mime_type
 * @property string|null $extension
 * @property string|null $description
 * @property int|null $file_folder_id
 * @property-read string|null $storage_url
 */
class CmsFile extends Model
{
    protected $table = 'cms_file';

    protected $guarded = [];

    public $timestamps = false;

    public function getStorageUrlAttribute(): ?string
    {
        if (! filled($this->path)) {
            return null;
        }

        $url = Storage::disk('public')->url($this->path);
        $parsedPath = parse_url($url, PHP_URL_PATH);

        if (is_string($parsedPath) && $parsedPath !== '') {
            return $parsedPath;
        }

        return $url;
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CmsFileFolder::class, 'file_folder_id');
    }
}
