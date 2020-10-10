<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;

class ParentCategory extends Model
{
    // public $visible = ['id', 'name','child_categories'];
    
    protected $casts = [
        'name' => HTMLDecode::class,
        'description' =>  HTMLDecode::class,
    ];

    public function child_categories() {
        return $this->hasMany('App\ChildCategory','parent_category_id');
    }

    public function products() {
        $product = new Product();
        return $this->hasMany('App\ChildCategory','parent_category_id')
        ->join('products','products.parent_category_id','child_categories.id')
        ->select(
            'products.*'
        )->limit(15)->withCasts($product->casts);
    }

}
