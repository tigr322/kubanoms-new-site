<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsPropstorage extends Model
{
    protected $table = 'oms_propstorage';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
