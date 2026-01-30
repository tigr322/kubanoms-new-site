<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
