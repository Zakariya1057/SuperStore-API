<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProductResultRequest;
use App\Http\Requests\SearchStoreResultRequest;
use App\Http\Requests\SearchSuggestionRequest;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use App\Services\Search\SearchService;

class SearchController extends Controller {

    private $sanitize_service, $search_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, SearchService $search_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->search_service = $search_service;
        $this->logger_service = $logger_service;
    }

    public function suggestions(SearchSuggestionRequest $request){

        $validated_data = $request->validated();
        
        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);
        
        $query = $data['query'];
        $supermarket_chain_id = $data['supermarket_chain_id'] ?? 1;

        $results = $this->search_service->suggestions($query, $supermarket_chain_id);

        $this->logger_service->log('search.suggestions',$request);
        return response()->json(['data' => $results]);
    }

    public function product_results(SearchProductResultRequest $request){

        $validated_data = $request->validated();

        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);

        $this->logger_service->log('search.product_results',$request);

        $page = $this->sanitize_service->sanitizeField((int)$request->page ?? 1);

        $results = $this->search_service->product_results($data, $page);

        return response()->json(['data' => $results]);

    }

    public function store_results(SearchStoreResultRequest $request){
        $this->logger_service->log('search.store_results',$request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $supermarket_chain_id = $data['supermarket_chain_id'] ?? 1;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        
        $results = $this->search_service->store_results($supermarket_chain_id, $latitude, $longitude);

        return response()->json(['data' => $results]);
    }
    
    
}
