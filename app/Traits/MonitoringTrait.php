<?php

namespace App\Traits;

use App\Product;

trait MonitoringTrait {

    protected function monitoring_products($user_id){
        $products = Product::select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->limit(10)->get();
        return $products;
    }

}

?>