<?php

namespace App\Models;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $casts = [
        'text' => HTMLDecode::class,
        'message_read' => 'bool',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public $visible = ['id', 'text', 'type', 'direction', 'message_read', 'created_at', 'updated_at'];
}