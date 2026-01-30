<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsFieldAccess extends Model
{
    protected $table = 'oms_field_access';

    public $timestamps = false;

    protected $guarded = [];
}
