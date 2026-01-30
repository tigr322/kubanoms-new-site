<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsAdStat extends Model
{
    protected $table = 'oms_ad_stat';

    protected $primaryKey = 'banner_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
