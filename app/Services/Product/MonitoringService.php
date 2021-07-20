<?php

namespace App\Services\Product;

use App\Models\MonitoredProduct;
use App\Models\Product;

class MonitoringService {
    public function all(int $user_id, int $region_id, int $supermarket_chain_id){
        $product = new Product();

        $products =  MonitoredProduct::where([ ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id], ['user_id', $user_id] ])
        ->select(
            'products.*' ,
            
            // Remove later
            'products.company_id as store_type_id',

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
        ->join('products','products.id','monitored_products.product_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('product_prices', 'products.id','=','product_prices.product_id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->withCasts(
            $product->casts
        )->groupBy('products.id')
        ->orderBy('monitored_products.created_at','DESC')
        ->get();

        foreach($products as $product_item){
            $product_item->monitoring = true;
        }

        return $products;

    }

    public function update($user_id, $product_id, $monitor){
        if ($monitor) {
            if( !MonitoredProduct::where([ ['user_id', $user_id], ['product_id', $product_id] ])->exists()) {
                $favourite = new MonitoredProduct();
                $favourite->product_id = $product_id;
                $favourite->user_id = $user_id;
                $favourite->save();
            }
        } else {
            MonitoredProduct::where([ ['user_id', $user_id], ['product_id', $product_id] ])->delete();
        }
    }
}
?>