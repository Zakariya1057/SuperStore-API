<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Review;

class ReviewController extends Controller
{
    public function index($product_id){
        $reviews = Review::where('product_id', $product_id)->join('users','users.id','reviews.user_id')->select(['reviews.id', 'name','title','text','rating'])->orderBy('reviews.created_at','DESC')->get();
        return response()->json(['data' => $reviews]);
    }

    public function show($product_id){
        $user_id = 2;

        $review = Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->orderBy('created_at','DESC')->get() ?? [];
        return response()->json(['data' => $review]);
    }

    public function create($product_id, Request $request){

        $user_id = 2;

        $validated_data = $request->validate([
            'data.text' => 'required',
            'data.rating' => 'required',
            'data.title' => 'required',
        ]);

        $data = $validated_data['data'];

        $text = $data['text'];
        $rating = $data['rating'];
        $title = $data['title'];

        if(  Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->exists() ){
            // Update Review Details
            Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->update([
                'title' => $title,
                'rating' => $rating,
                'text' => $text
            ]);
        } else {
            // Create Review
            $review = new Review();
            $review->user_id = $user_id;
            $review->product_id = $product_id;
            $review->title = $title;
            $review->text = $text;
            $review->rating = $rating;

            $review->save();
        }

        return response()->json(['data' => ['status' => 'success']]);

    }

}
