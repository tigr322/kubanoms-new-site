<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsGuestbook extends Model
{
    protected $table = 'oms_guestbook';

    public $timestamps = false;

    protected $guarded = [];
}
