<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageDocument extends Model
{
    protected $table = 'cms_page_documents';

    protected $fillable = [
        'page_id',
        'file_id',
        'title',
        'group_title',
        'document_date',
        'order',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'order' => 'integer',
        'document_date' => 'date',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(CmsFile::class, 'file_id');
    }
}
