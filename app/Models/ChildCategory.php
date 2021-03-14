<?php

namespace App\Models;

use App\Casts\HTMLDecode;
use Illuminate\Database\Eloquent\Model;

class ChildCategory extends Model
{
    protected $casts = [
        'name' => HTMLDecode::class
    ];

    public function products() {
        $product = new Product();
        return $this->hasMany('App\Models\CategoryProduct','child_category_id')
        ->join('products','products.id','category_products.product_id')
        ->select(
            'products.*'
        )->withCasts($product->casts);
    }
}
