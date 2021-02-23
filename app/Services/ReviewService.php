<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

class ReviewService {

    public function reviews($product_id): Collection {
        return Review::where('product_id', $product_id)
        ->join('users','users.id','reviews.user_id')
        ->select('reviews.*','users.name')
        ->orderBy('reviews.created_at','DESC')
        ->get();
    }

    public function get($user, $product_id): ?Review {
        $review = Review::where([ ['user_id', $user->id],['product_id',$product_id ] ])->orderBy('created_at','DESC')->first();
        
        if(!is_null($review)){
            $review->name = $user->name;
        }

        return $review;
    }

    public function create($user, $product_id, $data): ?Review {

        $user_id = $user->id;
        $name = $user->name;

        $text = $data['text'];
        $rating = $data['rating'];
        $title = $data['title'];

        if( Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->exists() ){
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

        return $review;
    }

    public function delete($user_id, $product_id){
        Review::where([ ['user_id', $user_id],['product_id',$product_id ] ])->delete();
    }

}
?>