<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class ProductGroup extends Model
{
    protected $casts = [
        'name' => HTMLDecode::class
    ];
}
