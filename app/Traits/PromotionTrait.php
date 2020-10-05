<?php

namespace App\Traits;

use App\Product;
use App\Promotion;

trait PromotionTrait {

    protected function store_promotions($store_id){
        $promotions = Promotion::where('promotions.store_type_id', $store_id)->get();

        foreach($promotions as $promotion){
            $promotion->products;
        }

        return $promotions;
    }

}

?>