<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenu extends Model
{
    protected $table = 'cms_menu';

    protected $guarded = [];

    public $timestamps = false;

    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id');
    }
}
