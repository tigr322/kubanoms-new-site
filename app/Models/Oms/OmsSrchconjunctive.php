<?php

namespace App\Models\Oms;

use Illuminate\Database\Eloquent\Model;

class OmsSrchconjunctive extends Model
{
    protected $table = 'oms_srchconjunctives';

    protected $primaryKey = 'word';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
