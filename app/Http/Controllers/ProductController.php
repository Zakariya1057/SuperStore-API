<?php

namespace App\Http\Controllers;

use App\Product;
use App\Recommended;
use App\Casts\HTMLDecode;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show(Product $product){

        // $product_details = Cache::remember('product_'.$product->id, 86400, function () use($product) {

            $product->ingredients;

            if (count($product->reviews) > 0){
                $product->reviews[0]->name = $product->reviews[0]->user->name;
            }
           
            $recommended = Recommended::where('product_id',$product->id)
            ->join('products','products.id','recommended_product_id')
            ->withCasts(
                $product->casts
            )->get();
    
            $product->recommended = $recommended;
            $product->promotion;

            // return $product;
        // });

        return response()->json(['data' => $product]);
    }

}