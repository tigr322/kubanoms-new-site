<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsAdTarget extends Model
{
    protected $table = 'oms_ad_target';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Composite key (banner_id + path) in legacy schema; treat as non-incrementing.
     */
    protected $guarded = [];

    public $timestamps = false;
}
