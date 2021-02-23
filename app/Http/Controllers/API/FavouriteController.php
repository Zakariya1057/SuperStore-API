<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\FavouriteService;
use App\Services\SanitizeService;

class FavouriteController extends Controller {

    private $sanitize_service, $favourite_service;

    function __construct(SanitizeService $sanitize_service, FavouriteService $favourite_service){
        $this->sanitize_service = $sanitize_service;
        $this->favourite_service = $favourite_service;
    }

    public function index(Request $request){
        $user_id = $request->user()->id;

        $products = $this->favourite_service->products($user_id);

        return response()->json(['data' => $products ]);
    }

    public function update($product_id, Request $request){
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.favourite' => 'required|bool',
        ]);

        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $favourite = (bool)$this->sanitize_service->sanitizeField($validated_data['data']['favourite']);

        $this->favourite_service->update($user_id, $product_id, $favourite);

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
