<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use App\Services\SanitizeService;
use App\Services\SearchService;
class SearchController extends Controller {

    private $sanitize_service, $search_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, SearchService $search_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->search_service = $search_service;
        $this->logger_service = $logger_service;
    }

    public function suggestions(Request $request){

        $validated_data = $request->validate([
            'data.query' => 'required',
            'data.store_type_id' => 'required'
        ]);
        
        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);
        
        $query = $data['query'];
        $store_type_id = $data['store_type_id'];

        $results = $this->search_service->suggestions($query, $store_type_id);

        $this->logger_service->log('search.suggestions',$request);
        return response()->json(['data' => $results]);
    }

    public function product_results(Request $request){

        $validated_data = $request->validate([
            'data.store_type_id' => 'required',

            'data.query' => 'required',
            'data.type'  => 'required',

            'data.sort' => '', // Rating, Price, Sugar, etc.
            'data.order' => '', // asc/desc

            'data.dietary' => '', // Halal, Vegetarian
            'data.child_category' => '',
            'data.brand' => '',
            'data.promotion' => '',

            'data.text_search' => ''
        ]);

        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);

        $this->logger_service->log('search.product_results',$request);

        $page = $this->sanitize_service->sanitizeField((int)$request->page ?? 1);

        $results = $this->search_service->product_results($data, $page);

        return response()->json(['data' => $results]);

    }

    public function store_results(Request $request){
        $this->logger_service->log('search.store_results',$request);

        $validated_data = $request->validate([
            'data.store_type_id' => 'required',
            'data.latitude' => '',
            'data.longitude' => '',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $store_type_id = $data['store_type_id'];
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        
        $results = $this->search_service->store_results($store_type_id, $latitude, $longitude);

        return response()->json(['data' => $results]);
    }
    
    
}
