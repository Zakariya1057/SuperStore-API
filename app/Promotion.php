<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class Promotion extends Model
{
    public $casts = [
        'name' => HTMLDecode::class
    ];
}
