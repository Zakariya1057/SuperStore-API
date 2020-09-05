<?php

namespace App;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    //
    protected $casts = [
        'city' => HTMLDecode::class,
        'address_line1' => HTMLDecode::class,
        'address_line2' => HTMLDecode::class,
        'address_line3' => HTMLDecode::class,

    ];
}
