<?php

namespace App\Http\Controllers\API;

use App\Models\GroceryList;
use App\Http\Controllers\Controller;
use App\Services\ListService;
use App\Services\SanitizeService;
use Illuminate\Http\Request;

class ListController extends Controller {

    private $sanitize_service, $list_service;

    function __construct(SanitizeService $sanitize_service, ListService $list_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_service = $list_service;
    }

    public function index(Request $request){
        $user_id = $request->user()->id;
        $lists = GroceryList::where('user_id', $user_id)->orderBy('created_at', 'DESC')->get();
        return response()->json(['data' => $lists]);
    }

    public function create(Request $request){

        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.name' => 'required|max:255',
            'data.identifier' => 'required',
            'data.store_type_id' => 'required',
            'data.items' => ''
        ]);
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $this->list_service->create($data, $user_id);
    
        return $this->index($request);

    }

    public function show(Request $request, $list_id){
        $user_id = $request->user()->id;

        $list_id = $this->sanitize_service->sanitizeField($list_id);
        $list = $this->list_service->show_list($list_id, $user_id);

        if($list instanceOf Request){
            return $list;
        } else {
            return response()->json(['data' => $list]);
        }
        
    }

    public function delete(Request $request){
        // Delete shopping list and all shopping items within
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.identifier' => 'required',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $identifier = $data['identifier'];

        $this->list_service->delete($identifier, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(Request $request){
        // Item ticked off, or quantity changed
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.identifier' => 'required',
            'data.store_type_id' => 'required',
            'data.name' => 'required',
            'data.items' => '',
            'data.mode' => ''
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $this->list_service->update($data, $user_id);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function restart(Request $request, $list_id){

        $user_id = $request->user()->id;

        $list_id = $this->sanitize_service->sanitizeField($list_id);

        $this->list_service->reset($list_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
        
    }

}
