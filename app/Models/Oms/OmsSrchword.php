<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSrchword extends Model
{
    protected $table = 'oms_srchwords';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
