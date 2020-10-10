<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Casts\HTMLDecode;
use App\Casts\PromotionCalculator;

class Promotion extends Model
{
    public $casts = [
        'name' => HTMLDecode::class,
        'discount' => PromotionCalculator::class
    ];

    public function products() {
        return $this->hasMany('App\Product')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->join('child_categories','child_categories.id','products.parent_category_id')
        ->join('parent_categories','child_categories.parent_category_id','parent_categories.id')
        ->select(
            'products.*',            
            'parent_categories.id as parent_category_id',
            'parent_categories.name as parent_category_name',
            'promotions.id as promotion_id',
            'promotions.name as discount'
        )->withCasts($this->casts);
    }
}
