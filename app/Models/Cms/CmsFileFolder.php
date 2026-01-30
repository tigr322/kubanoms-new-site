<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsFileFolder extends Model
{
    protected $table = 'cms_file_folder';

    protected $guarded = [];

    public $timestamps = false;

    public function files(): HasMany
    {
        return $this->hasMany(CmsFile::class, 'file_folder_id');
    }
}
