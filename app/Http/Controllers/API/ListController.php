<?php

namespace App\Http\Controllers\API;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Http\Controllers\Controller;
use App\Models\GroceryListItem;
use App\Services\ListService;
use App\Services\LoggerService;
use App\Services\SanitizeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListController extends Controller {

    private $sanitize_service, $list_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, ListService $list_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_service = $list_service;
        $this->logger_service = $logger_service;
    }

    public function index($store_type_id, Request $request){
        $user = $request->user();

        $user_id = $user->id;
        
        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);

        $this->logger_service->log('list.index.'.$store_type_id, $request);

        $lists = GroceryList::where([ ['user_id', $user_id],['store_type_id', $store_type_id] ])->orderBy('created_at', 'DESC')->get();
        return response()->json(['data' => $lists]);
    }

    public function create(Request $request){

        $user_id = $request->user()->id;

        $this->logger_service->log('list.create', $request);

        $validated_data = $request->validate([
            'data.name' => 'required|max:255',
            'data.identifier' => 'required',
            'data.store_type_id' => 'required|integer',
            'data.items' => ''
        ]);
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list = $this->list_service->create($data, $user_id);
    
        return response()->json(['data' => $list]);

    }

    public function show(Request $request, $list_id){
        $user_id = $request->user()->id;
        
        $this->logger_service->log('list.show', $request);

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

        $this->logger_service->log('list.delete', $request);

        $validated_data = $request->validate([
            'data.list_id' => 'required',
        ]);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list_id = $data['list_id'];

        $this->list_service->delete($list_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(Request $request){
        // Item ticked off, or quantity changed
        $user_id = $request->user()->id;

        $this->logger_service->log('list.update', $request);

        $validated_data = $request->validate([
            'data.list_id' => 'required',
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

    public function restart(Request $request){

        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.list_id' => 'required'
        ]);

        $this->logger_service->log('list.restart', $request);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        $list_id = $data['list_id'];

        $this->list_service->reset($list_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
        
    }


    // Offline Sync

    public function offline_delete(Request $request){
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.list_ids' => 'required'
        ]);

        $this->logger_service->log('list.offline.delete', $request);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list_ids = $data['list_ids'];
        // Loop through all their ids and delete

        if(count($list_ids) > 0){
            foreach($list_ids as $list_id){
                $list = GroceryList::where('user_id', $user_id)->where('id', $list_id)->first();

                if(!is_null($list)){
                    GroceryListItem::where('list_id', $list_id)->delete();
                    GroceryList::where('user_id', $user_id)->where('id', $list_id)->delete();
                }
            }
        }
       
        return response()->json(['data' => ['status' => 'success']]);
    }


    public function offline_edited(Request $request){
        $user_id = $request->user()->id;

        $validated_data = $request->validate([
            'data.lists' => 'required'
        ]);

        $this->logger_service->log('list.offline.delete', $request);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $lists = $data['lists'];

        $this->list_service->sync_edited_lists($lists, $user_id);
       
        return response()->json(['data' => ['status' => 'success']]);
        
    }

}
