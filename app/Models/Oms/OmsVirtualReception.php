<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OmsVirtualReception extends Model
{
    protected $table = 'oms_virtual_reception';

    public $timestamps = false;

    protected $guarded = [];

    public function attachments(): HasMany
    {
        return $this->hasMany(OmsVirtualReceptionAttachment::class, 'virtual_reception_id');
    }
}
