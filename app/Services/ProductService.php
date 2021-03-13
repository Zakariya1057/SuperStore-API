<?php

namespace App\Services;

use App\Models\FavouriteProducts;
use App\Models\FeaturedItem;
use App\Models\MonitoredProduct;
use App\Models\Product;
use App\Models\Recommended;

class ProductService {

    public function get(int $product_id, $user): ?Product {

        $product = new Product();
        $casts = $product->casts;

        $product = Product::where('products.id',$product_id)
        ->select(
            'products.*',
            'parent_categories.id as parent_category_id',
            'parent_categories.name as parent_category_name',
            'promotions.id as promotion_id',
            'promotions.name as promotion'
        )
        ->join('category_products','category_products.product_id','products.id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->withCasts($casts)
        ->get()
        ->first();

        if(!$product){
            return null;
        }


        $product->ingredients;

        if (count($product->reviews) > 0){
            $product->reviews[0]->name = $product->reviews[0]->user->name;
        }
        
        $recommended = Recommended::where([ ['recommended.product_id',$product->id] ])
        ->join('products','products.id','recommended_product_id')
        ->withCasts(
            $product->casts
        )->get();

        $product->recommended = $recommended;

        $favourite = $monitoring = false;

        if(!is_null($user)){
            $user_id = $user->id;

            $favourite  = FavouriteProducts::where([ ['user_id', $user_id], ['product_id', $product->id] ])->exists();
            $monitoring = MonitoredProduct::where([ ['user_id', $user_id], ['product_id', $product->id] ])->exists();
        }
        
        $product->favourite = $favourite;
        $product->monitoring = $monitoring;

        return $product;
    }

    public function featured(){
        $product = new Product();
        return FeaturedItem::select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->whereRaw('type = "products"')
        ->join('products', 'products.id','=','featured_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->orderBy('featured_items.updated_at', 'DESC')
        ->limit(10)->groupBy('category_products.product_id')->withCasts($product->casts)->get() ?? [];
    }

}
?>