<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ListItemService;
use App\Services\LoggerService;
use App\Services\SanitizeService;
use Illuminate\Http\Request;

class ListItemController extends Controller {

    private $sanitize_service, $list_item_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, ListItemService $list_item_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_item_service = $list_item_service;
        $this->logger_service = $logger_service;
    }

    public function create($list_id, Request $request){

        $this->logger_service->log('list item.create', $request);

        $validated_data = $request->validate([
            'data.product_id' => 'required',
            'data.parent_category_id' => 'required'
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $this->list_item_service->create($list_id, $data);
        
        // Set off message queue to update list total.
        return response()->json(['data' => ['status' => 'success']]);

    }
    
    public function update($list_id, Request $request){
        // Item ticked off, or quantity changed

        $this->logger_service->log('list item.update', $request);

        $validated_data = $request->validate([
            'data.product_id' => 'required',
            'data.quantity' => 'required',
            'data.ticked_off' => 'required'
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $user_id = $request->user()->id;

        $this->list_item_service->update($list_id, $data, $user_id);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function delete($list_id, Request $request){

        $this->logger_service->log('list item.delete', $request);

        $validated_data = $request->validate([
            'data.product_id' => 'required',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $product_id = $data['product_id'];
        $user_id = $request->user()->id;

        $this->list_item_service->delete($list_id, $product_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
    }

}
