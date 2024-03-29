<?php

namespace App\Services\Product;

use App\Models\FavouriteProducts;
use App\Models\FeaturedItem;
use App\Models\MonitoredProduct;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Recommended;
use App\Services\Sanitize\SanitizeService;

class ProductService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function get(int $region_id, int $supermarket_chain_id, int $product_id, $user): ?Product {

        $product = new Product();
        $casts = $product->casts;

        $product = Product::where('products.id',$product_id)
        ->select(
            'products.*',

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',

            'child_categories.id as child_category_id',
            'child_categories.name as child_category_name',

            'parent_categories.id as parent_category_id',
            'parent_categories.name as parent_category_name',
        )
        
        ->join('product_prices','product_prices.product_id','products.id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->join('child_categories','category_products.child_category_id','child_categories.id')

        ->withCasts($casts)

        ->where([ 
            ['product_prices.region_id', $region_id], 
            ['product_prices.supermarket_chain_id', $supermarket_chain_id] 
        ])

        ->get()
        
        ->first();

        if(!$product){
            return null;
        }

        $product->region_id = $region_id;
        $product->supermarket_chain_id = $supermarket_chain_id;

        $promotion = $product->promotion;
        if(!is_null($promotion)){
            $product->promotion = $promotion;
        }

        $product->images;

        $product->ingredients;

        $product->nutritions;

        if (count($product->reviews) > 0){
            $product->reviews[0]->name = $product->reviews[0]->user->name;
        }
        
        $product->features = is_null($product->features) ? null : $this->sanitize_service->decodeAllFields($product->features);
        $product->dimensions = is_null($product->dimensions) ? null : $this->sanitize_service->decodeAllFields($product->dimensions);

        $product->recommended = Recommended::where([ ['recommended.product_id',$product->id], ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id] ])
        ->select(
            'products.*',

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',
        )
        ->join('products','products.id','recommended_product_id')
        ->join('product_prices','product_prices.product_id','products.id')
        ->withCasts(
            $product->casts
        )->where('products.enabled', 1)->get();

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

    public function featured(int $region_id, int $supermarket_chain_id){
        return Product::select(
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
        
        ->where([ ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id] ])
        
        ->join('product_prices', 'products.id','=','product_prices.product_id')
        
        ->join('category_products','category_products.product_id','products.id')

        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')

        ->inRandomOrder()
        
        ->limit(20)
        
        ->get();
    }

    public function on_sale($supermarket_chain_id){
        $product = new Product();
        
        return Product::where('product_prices.supermarket_chain_id', $supermarket_chain_id)
        ->where(function($query) {
            $query->where('is_on_sale', 1)->orwhereNotNull('promotion_id');
        })
        ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->whereNotNull('products.small_image')
        ->orderBy('products.price', 'DESC')
        ->where('products.enabled', 1)
        ->limit(15)->groupBy('category_products.product_id')->withCasts($product->casts)->get() ?? [];
    }

}
?>