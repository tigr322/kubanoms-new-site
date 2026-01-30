<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSetup extends Model
{
    protected $table = 'oms_setup';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
