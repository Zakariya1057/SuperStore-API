<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class Review extends Model
{

    protected $casts = [
        'text' => HTMLDecode::class,
        'title' => HTMLDecode::class,  
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user() {
        return $this->belongsTo('App\Models\User')->select('name');
    }
}