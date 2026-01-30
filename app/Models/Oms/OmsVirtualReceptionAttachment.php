<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OmsVirtualReceptionAttachment extends Model
{
    protected $table = 'oms_virtual_reception_attachment';

    public $timestamps = false;

    protected $guarded = [];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(OmsVirtualReception::class, 'virtual_reception_id');
    }
}
