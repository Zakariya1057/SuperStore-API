<?php

namespace App\Models;
use App\Casts\HTMLDecode;

use Illuminate\Database\Eloquent\Model;
class Facility extends Model
{
    public $casts = [
        'name' => HTMLDecode::class
    ];
}
