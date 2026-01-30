<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSrchpath extends Model
{
    protected $table = 'oms_srchpathes';

    protected $primaryKey = 'path_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
