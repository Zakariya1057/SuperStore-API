<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class Review extends Model
{

    public $visible = ['id', 'name','title','text','rating'];

    protected $casts = [
        'text' => HTMLDecode::class,
        'title' => HTMLDecode::class,  
    ];

    public function user() {
        return $this->belongsTo('App\User')->select('name');
    }
}