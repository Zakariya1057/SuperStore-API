<?php

namespace App\Models;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $casts = [
        'text' => HTMLDecode::class,
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $visible = ['id', 'text', 'type', 'direction', 'created_at', 'updated_at'];
}