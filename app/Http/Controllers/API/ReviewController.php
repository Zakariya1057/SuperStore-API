<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use App\Services\SanitizeService;

class ReviewController extends Controller {

    private $sanitize_service, $review_service;

    function __construct(SanitizeService $sanitize_service, ReviewService $review_service){
        $this->sanitize_service = $sanitize_service;
        $this->review_service = $review_service;
    }

    public function index($product_id){
        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $reviews = $this->review_service->reviews($product_id);
        return response()->json(['data' => $reviews]);
    }

    public function show(Request $request, $product_id){
        $user = $request->user();
        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $review = $this->review_service->get($user, $product_id);

        return response()->json(['data' => $review]);
    }

    public function delete(Request $request, $product_id){
        $user_id = $request->user()->id;
        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $this->review_service->delete($user_id, $product_id);
        
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function create($product_id, Request $request){

        $user = $request->user();

        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $validated_data = $request->validate([
            'data.text' => 'required',
            'data.rating' => 'required',
            'data.title' => 'required',
        ]);

        $data = $validated_data['data'];
        $data = $this->sanitize_service->sanitizeAllFields($data);

        $review = $this->review_service->create($user, $product_id, $data);

        return response()->json(['data' => [$review]]);

    }

}
