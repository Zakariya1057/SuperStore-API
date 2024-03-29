<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class ParentCategory extends Model
{
    protected $casts = [
        'name' => HTMLDecode::class,
        'description' =>  HTMLDecode::class,
        'enabled' => 'Bool'
    ];

    public function child_categories() {
        return $this->hasMany('App\Models\ChildCategory','parent_category_id');
    }

    public function products() {
        $product = new Product();
        return $this->hasMany('App\Models\CategoryProduct','parent_category_id')
        ->join('products','products.id','category_products.product_id')
        ->select(
            'products.*'
        )->limit(15)->withCasts($product->casts);
    }

}
