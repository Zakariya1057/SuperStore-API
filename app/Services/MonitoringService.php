<?php

namespace App\Services;

use App\Models\MonitoredProduct;
use App\Models\Product;

class MonitoringService {
    public function all($user_id, $store_type_id){
        $product = new Product();

        $products =  MonitoredProduct::where([ ['products.store_type_id', $store_type_id], ['user_id', $user_id] ])
        ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('products','products.id','monitored_products.product_id')
        ->join('category_products','category_products.product_id','products.id')
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