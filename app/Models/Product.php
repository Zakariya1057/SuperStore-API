<?php

namespace App\Models;

use App\Casts\CurrencyCast;
use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
use App\Casts\Image;

class Product extends Model
{
    public $casts = [
        'name' => HTMLDecode::class,

        'description' => HTMLDecode::class,
        
        'features' => 'json',
        'dimensions' => 'json',

        'price' => 'double',
        'currency' => CurrencyCast::class,

        'is_on_sale' => 'boolean',

        'old_price' => 'double',
        'avg_rating' => 'double',

        'sale_ends_at' => 'datetime:Y-m-d H:i:s',

        'brand' => HTMLDecode::class,

        'allergen_info' => HTMLDecode::class,
        'dietary_info' => HTMLDecode::class,

        'large_image' => Image::class,
        'small_image' => Image::class,

        'parent_category_name' => HTMLDecode::class,
        'child_category_name' => HTMLDecode::class,
    ];

    public function images(){
        return $this->hasMany('App\Models\ProductImage');
    }

    public function ingredients() {
        return $this->hasMany('App\Models\Ingredient');
    }

    public function reviews() {
        return $this->hasMany('App\Models\Review')->orderBy('reviews.created_at','DESC')->limit(1);
    }

    public function promotion(){
        return $this->belongsTo('App\Models\Promotion');
    }

}
