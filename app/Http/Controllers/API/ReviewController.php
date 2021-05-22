<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\Product\ReviewService;
use App\Services\Sanitize\SanitizeService;

class ReviewController extends Controller {

    private $sanitize_service, $review_service;

    function __construct(SanitizeService $sanitize_service, ReviewService $review_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->review_service = $review_service;
        $this->logger_service = $logger_service;
    }

    public function create($product_id, Request $request){

        $user = $request->user();

        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $this->logger_service->log('review.create', $request);

        $validated_data = $request->validate([
            'data.text' => 'required',
            'data.rating' => 'required',
            'data.title' => 'required',
        ]);

        $data = $validated_data['data'];
        $data = $this->sanitize_service->sanitizeAllFields($data);

        $review = $this->review_service->create($user, $product_id, $data);

        return response()->json(['data' => $review]);

    }

    public function index($product_id, Request $request){
        $product_id = $this->sanitize_service->sanitizeField($product_id);
        
        $this->logger_service->log('review.index', $request);

        $reviews = $this->review_service->reviews($product_id);
        return response()->json(['data' => $reviews]);
    }

    public function show(Request $request, $product_id){
        $user = $request->user();
        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $this->logger_service->log('review.show', $request);

        $review = $this->review_service->get($user, $product_id);

        return response()->json(['data' => $review]);
    }

    public function delete(Request $request, $product_id){
        $user_id = $request->user()->id;
        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $this->logger_service->log('review.delete', $request);

        $this->review_service->delete($user_id, $product_id);
        
        return response()->json(['data' => ['status' => 'success']]);
    }

}
