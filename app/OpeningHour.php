<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OpeningHour extends Model
{

    protected $casts = [
        'opens_at' => 'datetime:h:iA',
        'closes_at' => 'datetime:h:iA',
    ];

}
