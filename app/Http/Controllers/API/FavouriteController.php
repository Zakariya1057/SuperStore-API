<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Product\FavouriteService;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;

class FavouriteController extends Controller {

    private $sanitize_service, $favourite_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, FavouriteService $favourite_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->favourite_service = $favourite_service;
        $this->logger_service = $logger_service;
    }

    public function index(Request $request){
        $user_id = $request->user()->id;

        $region_id = $this->sanitize_service->sanitizeField($request->input('region_id') ?? 1);

        $this->logger_service->log('favourite.index', $request);
        $products = $this->favourite_service->products($region_id, $user_id);

        return response()->json(['data' => $products ]);
    }

    public function update($product_id, Request $request){
        $user_id = $request->user()->id;

        $this->logger_service->log('favourite.update', $request);

        $validated_data = $request->validate([
            'data.favourite' => 'required|bool',
        ]);

        $product_id = $this->sanitize_service->sanitizeField($product_id);
        $favourite = (bool)$this->sanitize_service->sanitizeField($validated_data['data']['favourite']);

        $this->favourite_service->update($user_id, $product_id, $favourite);

        return response()->json(['data' => ['status' => 'success']]);

    }
    
}
