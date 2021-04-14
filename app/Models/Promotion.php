<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

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

        'store_type_id',
        'products',
        'site_promotion_id',

        'expires',
        'starts_at',
        'ends_at',

        'enabled'
    ];


    public $casts = [
        'name' => HTMLDecode::class,
        'price' => 'double',
        
        'expires' => 'Bool',

        'enabled' => 'Bool',

        'starts_at' => 'datetime:Y-m-d H:i:s',
        'ends_at' => 'datetime:Y-m-d H:i:s',
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
        )->where('products.enabled', 1)->groupBy('products.id')->withCasts($this->casts);
    }
}
