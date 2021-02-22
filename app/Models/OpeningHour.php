<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class OpeningHour extends Model
{
    public $casts = [
        'opens_at' => 'datetime:h:iA',
        'closes_at' => 'datetime:h:iA',
        'closed_today' => 'boolean'
    ];

}
