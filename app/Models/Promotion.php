<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
use App\Casts\PromotionCalculator;

class Promotion extends Model
{
    protected $visible = [
        'id',
        'url',
        'name',

        'quantity',
        'price',
        'for_quantity',

        'minumum',
        'maximum',

        'store_type_id',
        'products',
        'site_promotion_id',

        'expires',
        'starts_at',
        'ends_at',
    ];


    public $casts = [
        'name' => HTMLDecode::class,
        'price' => 'double'
    ];

    public function products() {
        return $this->hasMany('App\Models\Product','promotion_id')
        ->join('promotions','promotions.id','products.promotion_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','parent_categories.id','category_products.parent_category_id')
        ->select(
            'products.*',            
            'parent_categories.id as parent_category_id',
            'parent_categories.name as parent_category_name',
        )->groupBy('products.id')->withCasts($this->casts);
    }
}
