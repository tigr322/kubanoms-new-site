<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsCtlcolumn extends Model
{
    protected $table = 'oms_ctlcolumns';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
