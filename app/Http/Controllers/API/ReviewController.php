<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Http\Controllers\Controller;
use App\Services\SanitizeService;

class ReviewController extends Controller {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function index($product_id){
        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $reviews = Review::where('product_id', $product_id)->join('users','users.id','reviews.user_id')->select('reviews.*','users.name')->orderBy('reviews.created_at','DESC')->get();
        return response()->json(['data' => $reviews]);
    }

    public function show(Request $request, $product_id){
        $user = $request->user();
        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $reviews = Review::where([ ['user_id', $user->id],['product_id',$product_id ] ])->orderBy('created_at','DESC')->get() ?? [];
        
        foreach($reviews as $review){
            $review->name = $user->name;
        }

        return response()->json(['data' => $reviews]);
    }

    public function delete(Request $request, $product_id){
        $user_id = $request->user()->id;
        $product_id = $this->sanitize_service->sanitizeField($product_id);

        Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->delete();
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function create($product_id, Request $request){

        $user = $request->user();

        $user_id = $user->id;
        $name = $user->name;

        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $validated_data = $request->validate([
            'data.text' => 'required',
            'data.rating' => 'required',
            'data.title' => 'required',
        ]);

        $data = $validated_data['data'];
        $data = $this->sanitize_service->sanitizeAllFields($data);

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
            $review->user_id = (int)$user_id;
            $review->product_id = (int)$product_id;
            $review->title = $title;
            $review->text = $text;
            $review->rating = (int)$rating;

            $review->save();
        }

        $review = Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->orderBy('created_at','DESC')->get()->first();
        $review->name = $name;

        return response()->json(['data' => [$review]]);

    }

}
