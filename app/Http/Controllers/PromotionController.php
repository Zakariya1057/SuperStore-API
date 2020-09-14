<?php

namespace App\Http\Controllers;

use App\Promotion;

class PromotionController extends Controller
{
    public function index($promotion_id){
        $promotion = Promotion::where('id', $promotion_id)->get()->first();
        $promotion->products;
        return response()->json(['data' => $promotion]);
    }
}
