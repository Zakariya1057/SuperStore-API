<?php

namespace App\Traits;

use App\Product;

trait MonitoringTrait {

    protected function monitoring_products($user_id){
        $products = Product::limit(10)->get();
        return $products;
    }

}

?>