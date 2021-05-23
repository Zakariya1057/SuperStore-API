<?php

namespace App\Models;

use App\Casts\FlyerCast;
use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class Flyer extends Model
{
    public $casts = [
        'name' => HTMLDecode::class,

        'url' => FlyerCast::class,
        

        'valid_from' => 'datetime:Y-m-d H:i:s',
        'valid_to' => 'datetime:Y-m-d H:i:s',

        'enabled' => 'Bool',
    ];
}
