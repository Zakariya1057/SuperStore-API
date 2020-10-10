<?php

namespace App\Http\Controllers\API;

use App\Promotion;
use App\Http\Controllers\Controller;
use App\Product;
use App\Casts\PromotionCalculator;

class PromotionController extends Controller
{
    public function index($promotion_id){
        $promotion = Promotion::where('id', $promotion_id)->get()->first();
        $promotion->products;
        // $product = new Product();
        // $casts = $product->casts;

        // $casts['discount'] = PromotionCalculator::class;

        // $promotion = Product::where('promotion.id',$promotion_id)
        // ->select(
        //     'products.*',
        //     'parent_categories.id as parent_category_id',
        //     'parent_categories.name as parent_category_name',
        //     'promotions.id as promotion_id',
        //     'promotions.name as discount'
        // )
        // ->join('child_categories','child_categories.id','products.parent_category_id')
        // ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        // ->join('parent_categories','child_categories.parent_category_id','parent_categories.id')
        // ->withCasts($casts)
        // ->get()
        // ->first();

        return response()->json(['data' => $promotion]);
    }
}
