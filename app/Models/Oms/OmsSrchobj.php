<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSrchobj extends Model
{
    protected $table = 'oms_srchobj';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
