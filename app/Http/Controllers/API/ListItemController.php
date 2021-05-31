<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListItemCreateRequest;
use App\Http\Requests\ListItemDeleteRequest;
use App\Http\Requests\ListItemUpdateRequest;
use App\Services\GroceryList\GroceryListItemService;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Support\Facades\Auth;

class ListItemController extends Controller {

    private $sanitize_service, $list_item_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, GroceryListItemService $list_item_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_item_service = $list_item_service;
        $this->logger_service = $logger_service;
    }

    public function create($list_id, ListItemCreateRequest $request){

        $this->logger_service->log('list item.create', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list_item = $this->list_item_service->create($list_id, $data);
        
        // Set off message queue to update list total.
        return response()->json(['data' => $list_item]);
    }
    
    public function update($list_id, ListItemUpdateRequest $request){
        // Item ticked off, or quantity changed

        $this->logger_service->log('list item.update', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $user_id = Auth::id();

        $this->list_item_service->update($list_id, $data, $user_id);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function delete($list_id, ListItemDeleteRequest $request){

        $this->logger_service->log('list item.delete', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $product_id = $data['product_id'];
        $user_id = Auth::id();

        $this->list_item_service->delete($list_id, $product_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
    }

}
