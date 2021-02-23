<?php

namespace App\Services;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use Exception;

class ListService extends ListSharedService {

    public function create($data, $user_id){
        $items = $data['items'] ?? [];

        $list_name = $data['name'];
        $store_type_id = $data['store_type_id'];
        $identifier = $data['identifier'];

        if( !GroceryList::where('identifier',$identifier)->exists() ){

            $list = GroceryList::create([
                'name' => $list_name,
                'user_id' => $user_id,
                'store_type_id' => $store_type_id,
                'identifier' => $identifier
            ]);

            $this->update_list_items($list->id, $items, 'overwrite');
            event(new GroceryListChangedEvent($list));
        }
    }

    public function update($data, $user_id){

        $items = $data['items'] ?? [];

        $name = $data['name'];
        $store_type_id = $data['store_type_id'];
        $mode = $data['mode'] ?? '';

        $list = GroceryList::where([['identifier',$data['identifier']],['user_id', $user_id]])->get()->first();

        if(is_null($list)){
            throw new Exception('No list found.', 404);
        } else {

            $list_id = $list->id;

            if($mode == 'delete'){
                GroceryListItem::where('list_id', $list_id)->delete();
                GroceryList::where('id', $list_id)->delete();
            } else {
                GroceryList::where([['identifier',$data['identifier']],['user_id', $user_id]])
                ->update([
                    'name' => $name,
                    'store_type_id' => $store_type_id
                ]);
    
                $this->update_list_items($list_id, $items, $mode);
                event(new GroceryListChangedEvent($list));
            }

        }

    }

    public function reset($list_id, $user_id){
        $list = GroceryList::where([ ['id',$list_id], ['user_id', $user_id] ])->get()->first();

        if($list){
            GroceryListItem::where([['list_id', $list->id]])
            ->update([
                'ticked_off' => 0
            ]);
        }
    }

    public function delete($identifier, $user_id){
        $list = GroceryList::where([['identifier',$identifier],['user_id', $user_id]])->get()->first();

        if($list){
            GroceryListItem::where('list_id',$list->id)->delete();
            GroceryList::where([ ['id',$list->id], ['user_id', $user_id] ])->delete();
        }
    }
}
?>