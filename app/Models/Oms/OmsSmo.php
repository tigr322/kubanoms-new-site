<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSmo extends Model
{
    protected $table = 'oms_smo';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}
