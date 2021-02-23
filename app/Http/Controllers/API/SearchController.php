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

    public function suggestions($query, Request $request){
        $query = $this->sanitize_service->sanitizeField($query);
        $results = $this->search_service->suggestions($query);
        $this->logger_service->log('search.suggestions',$request);
        return response()->json(['data' => $results]);
    }

    public function results(Request $request){

        $validated_data = $request->validate([
            'data.type' => 'required',
            'data.detail' => 'required',

            'data.sort' => '', // Rating, Price, Sugar, etc.
            'data.order' => '', // asc/desc

            'data.dietary' => '', // Halal, Vegetarian
            'data.child_category' => '',
            'data.brand' => '',

            'data.text_search' => ''
        ]);

        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);

        $this->logger_service->log('search.results',$request);

        $results = $this->search_service->results($data);

        return response()->json(['data' => $results]);

    }
    
    
}
