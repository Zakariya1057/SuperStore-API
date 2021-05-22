<?php

namespace App\Services\Product;

use App\Models\FavouriteProducts;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class FavouriteService {

    public function products(int $user_id): Collection {

        $product = new Product();
        
        $products = FavouriteProducts::where([ ['user_id', $user_id] ])
        ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('products','products.id','favourite_products.product_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->withCasts(
            $product->casts
        )->groupBy('products.id')
        ->where('products.enabled', 1)
        ->orderBy('favourite_products.created_at','DESC')->get();

        foreach($products as $product){
            $product->favourite = true;
        }
        
        return $products;

    }

    public function update(int $user_id, int $product_id, bool $favourite): void{

        if ($favourite == 'true') {
            if( !FavouriteProducts::where([ ['user_id', $user_id], ['product_id', $product_id] ])->exists()) {
                $favourite = new FavouriteProducts();
                $favourite->product_id = $product_id;
                $favourite->user_id = $user_id;
                $favourite->save();
            }
        } else {
            FavouriteProducts::where([ ['user_id', $user_id], ['product_id', $product_id] ])->delete();
        }

    }
    
}
?>