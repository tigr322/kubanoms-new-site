<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $original_name
 * @property string $path
 * @property string|null $mime_type
 * @property string|null $extension
 * @property string|null $description
 * @property int|null $file_folder_id
 */
class CmsFile extends Model
{
    protected $table = 'cms_file';

    protected $guarded = [];

    public $timestamps = false;

    public function folder(): BelongsTo
    {
        return $this->belongsTo(CmsFileFolder::class, 'file_folder_id');
    }
}
