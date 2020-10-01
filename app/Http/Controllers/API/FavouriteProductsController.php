<?php

namespace App\Http\Controllers\API;

use App\FavouriteProducts;
use App\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FavouriteProductsController extends Controller
{
    
    public function index(Request $request){
        $user_id = $request->user()->id;

        $product = new Product();
        
        $products = FavouriteProducts::where([ ['user_id', $user_id] ])
        ->select('products.*')
        ->join('products','products.id','favourite_products.product_id')->withCasts(
            $product->casts
        )->orderBy('favourite_products.created_at','DESC')->get();

        return response()->json(['data' => $products ]);
    }

    public function update($product_id, Request $request){
        $user_id = $request->user()->id;

        // Item ticked off, or quantity changed
        $validated_data = $request->validate([
            'data.favourite' => 'required',
        ]);

        $favourite = strtolower($validated_data['data']['favourite']);

        if ($favourite == 'true') {
            $favourite = new FavouriteProducts();
            $favourite->product_id = $product_id;
            $favourite->user_id = $user_id;
            $favourite->save();
        } else {
            FavouriteProducts::where([ ['user_id', $user_id], ['product_id', $product_id] ])->delete();
        }

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
