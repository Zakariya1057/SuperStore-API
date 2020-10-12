<?php

namespace App\Http\Controllers\API;

use App\Promotion;
use App\Http\Controllers\Controller;
use App\Product;
use App\Casts\PromotionCalculator;

class PromotionController extends Controller
{
    public function index($promotion_id){
        $promotion = Promotion::where('id', $promotion_id)->get()->first();
        $promotion->products;
        return response()->json(['data' => $promotion]);
    }
}
