<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
use App\Casts\Image;

class Promotion extends Model
{
    protected $visible = [
        'id',
        'url',
        'name',
        'title',

        'quantity',
        'price',
        'for_quantity',

        'minimum',
        'maximum',

        'supermarket_chain_id',
        'products',
        'site_promotion_id',

        'expires',
        'starts_at',
        'ends_at',

        'enabled'
    ];


    public $casts = [
        'name' => HTMLDecode::class,
        'title' => HTMLDecode::class,

        'price' => 'double',
        
        'large_image' => Image::class,
        'small_image' => Image::class,

        'expires' => 'Bool',

        'enabled' => 'Bool',

        'starts_at' => 'datetime:Y-m-d H:i:s',
        'ends_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function products() {
        return $this->hasMany('App\Models\ProductPrice','promotion_id')
        ->join('products','products.id','product_prices.product_id')
        ->join('promotions','promotions.id','product_prices.promotion_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','parent_categories.id','category_products.parent_category_id')
        ->select(
            'products.*',    

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',

            'parent_categories.id as parent_category_id',
            'parent_categories.name as parent_category_name',
        )->where('products.enabled', 1)->groupBy('products.id')->withCasts($this->casts);
    }
}
