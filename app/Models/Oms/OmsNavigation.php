<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsNavigation extends Model
{
    protected $table = 'oms_navigation';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
