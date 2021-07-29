<?php

namespace App\Http\Controllers\API;

use App\Models\GroceryList;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListCreateRequest;
use App\Http\Requests\ListDeleteRequest;
use App\Http\Requests\ListOfflineDeleteRequest;
use App\Http\Requests\ListOfflineEditedRequest;
use App\Http\Requests\ListRestartRequest;
use App\Http\Requests\ListUpdateRequest;
use App\Models\GroceryListItem;
use App\Services\GroceryList\GroceryListService;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListController extends Controller {

    private $sanitize_service, $list_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, GroceryListService $list_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_service = $list_service;
        $this->logger_service = $logger_service;
    }

    public function index($supermarket_chain_id, Request $request){
        $user = $request->user();

        $user_id = $user->id;

        $this->logger_service->log('list.index', $request);

        $lists = GroceryList::where([ 
            ['supermarket_chain_id', $supermarket_chain_id], 
            ['user_id', $user_id] 
        ])->orderBy('updated_at', 'DESC')->get();

        // Remove later
        foreach($lists as $list){
            $list->store_type_id = 2;
        }
        
        return response()->json(['data' => $lists]);
    }

    public function create(ListCreateRequest $request){

        $user_id = Auth::id();

        $this->logger_service->log('list.create', $request);

        $validated_data = $request->validated();
        
        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list = $this->list_service->create($data, $user_id);
    
        return response()->json(['data' => $list]);

    }

    public function show(Request $request, $list_id){
        $user_id = Auth::id();
        
        $this->logger_service->log('list.show', $request);

        $list_id = $this->sanitize_service->sanitizeField($list_id);
        $list = $this->list_service->show_list($list_id, $user_id);

        if($list instanceOf Request){
            return $list;
        } else {
            return response()->json(['data' => $list]);
        }
        
    }

    public function delete(ListDeleteRequest $request){
        // Delete shopping list and all shopping items within
        $user_id = Auth::id();

        $this->logger_service->log('list.delete', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $list_id = $data['list_id'];

        $this->list_service->delete($list_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(ListUpdateRequest $request){
        // Item ticked off, or quantity changed
        $user_id = Auth::id();

        $this->logger_service->log('list.update', $request);

        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $this->list_service->update($data, $user_id);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function restart(ListRestartRequest $request){

        $user_id = Auth::id();

        $validated_data = $request->validated();

        $this->logger_service->log('list.restart', $request);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        $list_id = $data['list_id'];

        $this->list_service->reset($list_id, $user_id);

        return response()->json(['data' => ['status' => 'success']]);
        
    }


    // Offline Sync
    public function offline_delete(ListOfflineDeleteRequest $request){
        $user_id = Auth::id();

        $validated_data = $request->validated();

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


    public function offline_edited(ListOfflineEditedRequest $request){
        $user_id = Auth::id();

        $validated_data = $request->validated();

        $this->logger_service->log('list.offline.delete', $request);

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);

        $lists = $data['lists'];

        $this->list_service->sync_edited_lists($lists, $user_id);
       
        return response()->json(['data' => ['status' => 'success']]);
        
    }

}
