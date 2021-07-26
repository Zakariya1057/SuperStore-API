<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\Image;

class SupermarketChain extends Model
{
    public $casts = [
        'large_logo' => Image::class,
        'small_logo' => Image::class,
        'enabled' => 'Bool'
    ];
}
