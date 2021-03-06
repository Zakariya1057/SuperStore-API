<?php

namespace App\Services;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
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
        $list_id = $data['list_id'];

        $list = GroceryList::where([['id', $list_id],['user_id', $user_id]])->get()->first();

        if(is_null($list)){
            throw new Exception('No list found.', 404);
        } else {

            if($mode == 'delete'){
                GroceryListItem::where('list_id', $list_id)->delete();
                GroceryList::where('id', $list_id)->delete();
            } else {
                GroceryList::where([['id', $list_id ],['user_id', $user_id]])
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

    public function delete($list_id, $user_id){
        $list = GroceryList::where([['id', $list_id],['user_id', $user_id]])->get()->first();

        if($list){
            GroceryListItem::where('list_id',$list->id)->delete();
            GroceryList::where([ ['id',$list->id], ['user_id', $user_id] ])->delete();
        }
    }


    // Additional Functionality
    public function recent_items($user_id){
        
        $product = new Product();

        return GroceryList::where('user_id', $user_id)
        ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('grocery_list_items','grocery_list_items.list_id','grocery_lists.id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->orderBy('grocery_lists.updated_at', 'DESC')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->limit(15)->groupBy('category_products.product_id')->withCasts($product->casts)->get();
    }

    public function lists_progress($user_id){
        return GroceryList::where('user_id', $user_id)
        ->orderByRaw('(ticked_off_items/ total_items) DESC, `grocery_lists`.`updated_at` DESC')
        ->limit(4)->get();
    }
}
?>