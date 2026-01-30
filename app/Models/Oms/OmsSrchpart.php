<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSrchpart extends Model
{
    protected $table = 'oms_srchparts';

    protected $primaryKey = 'path';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
