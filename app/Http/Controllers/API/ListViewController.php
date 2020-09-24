<?php

namespace App\Http\Controllers\API;

use App\GroceryList;
use App\GroceryListItem;
use App\Traits\GroceryListTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ListViewController extends Controller
{
    
    use GroceryListTrait;

    public function index(){
        //Use user_id to get all lists for user
        $user_id = 1;
        $lists = GroceryList::where('user_id', $user_id)->orderBy('created_at', 'DESC')->get();
        return response()->json(['data' => $lists]);
    }

    public function create(Request $request){

        $validated_data = $request->validate([
            'data.name' => 'required|max:255',
            'data.store_id' => 'required',
        ]);
        
        $list_name = $validated_data['data']['name'];
        $store_id = $validated_data['data']['store_id'];
        $user_id = 1;

        $list = new GroceryList();
        $list->name = $list_name;
        $list->store_id = $store_id;
        $list->user_id = $user_id;
        $list->save();

        return $this->index();

    }

    public function show($list_id){

        $user_id = 1;
        
        $list = $this->show_list($list_id, $user_id);

        return response()->json(['data' => $list]);
    }

    public function delete(Request $request){
        // Delete shopping list and all shopping items within
        $validated_data = $request->validate([
            'data.list_id' => 'required',
        ]);

        $data = $validated_data['data'];

        $user_id = 1;

        GroceryListItem::where('list_id',$data['list_id'])->delete();

        GroceryList::where([ ['id',$data['list_id']], ['user_id', $user_id] ])->delete();

        return response()->json(['data' => ['status' => 'success']]);
    }

    public function update(Request $request){
        // Item ticked off, or quantity changed
        $validated_data = $request->validate([
            'data.list_id' => 'required',
            'data.store_id' => 'required',
            'data.name' => 'required'
        ]);

        $data = $validated_data['data'];
        $user_id = 1;

        // $status = $data['status'];
        $name = $data['name'];
        $store_id = $data['store_id'];

        // $lower_status = strtolower($status);

        // if($lower_status != 'in progress' && $lower_status != 'completed' && $lower_status != 'not started' ){
        //     return response()->json(['data' => ['error' => 'Unknown List Status: ']], 422);
        // }

        GroceryList::where([['id',$data['list_id']],['user_id', $user_id]])
        ->update([
            'name' => $name,
            'store_id' => $store_id
        ]);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function restart($list_id){
        $user_id = 1;

        //Make sure that list belongs to user
        $list = GroceryList::where([ ['id',$list_id], ['user_id', $user_id] ])->get()->first();

        if($list){

            GroceryListItem::where([['list_id', $list->id]])
            ->update([
                'ticked_off' => 0
            ]);

        }

        return response()->json(['data' => ['status' => 'success']]);
        
    }

}
