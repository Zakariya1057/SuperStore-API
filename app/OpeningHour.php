<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
class OpeningHour extends Model
{

    protected $casts = [
        'opens_at' => 'datetime:h:iA',
        'closes_at' => 'datetime:h:iA',
        'closed_today' => 'boolean'
    ];

}
