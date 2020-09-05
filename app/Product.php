<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
use App\Casts\Image;

class Product extends Model
{
    public $casts = [
        'name' => HTMLDecode::class,
        'price' => 'decimal:2',
        'avg_rating' => 'double',

        'large_image' => Image::class,
        'small_image' => Image::class,
    ];

    public function ingredients() {
        return $this->hasMany('App\Ingredient');
    }

    public function reviews() {
        return $this->hasMany('App\Review')->limit(1);
    }

    public function promotion(){
        return $this->belongsTo('App\Promotion');
    }

}
