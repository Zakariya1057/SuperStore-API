<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Review;

class ReviewController extends Controller
{
    public function index($product_id){
        $reviews = Review::where('product_id', $product_id)->join('users','users.id','reviews.user_id')->select(['reviews.id', 'name','title','text','rating'])->get();
        return response()->json(['data' => $reviews]);
    }
}
