<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsBasket extends Model
{
    protected $table = 'oms_basket';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
