<?php

namespace App\Services\Product;

use App\Models\FavouriteProducts;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class FavouriteService {

    public function products(int $region_id, int $supermarket_chain_id, int $user_id): Collection {

        $product = new Product();
        
        $products = FavouriteProducts::where([ ['user_id', $user_id] ])
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
            'parent_categories.name as parent_category_name'
        )
        ->join('products','products.id','favourite_products.product_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->join('product_prices','product_prices.product_id','products.id')
        ->withCasts(
            $product->casts
        )->groupBy('products.id')
        ->where([ ['region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id], ['products.enabled', 1]])
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