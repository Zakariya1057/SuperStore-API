<?php

namespace App\Services;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use App\Models\StoreType;
use Exception;

class ListService extends ListSharedService {

    public function create($data, $user_id){
        $list_name = $data['name'];
        $store_type_id = $data['store_type_id'];
        $identifier = $data['identifier'];

        $currency = StoreType::where('id', $store_type_id)->get()->first()->currency;

        if( !GroceryList::where('identifier',$identifier)->exists() ){

            $list = GroceryList::create([
                'name' => $list_name,
                'user_id' => $user_id,
                'status' => 'Not Started',
                'currency' => $currency,
                'store_type_id' => $store_type_id,
                'identifier' => $identifier
            ]);

            event(new GroceryListChangedEvent($list));

            return GroceryList::whereId($list->id)->get()->first();
        }
    }

    public function update($data, $user_id){

        $name = $data['name'];
        $store_type_id = $data['store_type_id'];
        $list_id = $data['list_id'];

        $list = GroceryList::where([['id', $list_id],['user_id', $user_id]])->get()->first();

        if(is_null($list)){
            throw new Exception('No list found.', 404);
        } else {

            GroceryList::where([['id', $list_id ],['user_id', $user_id]])
            ->update([
                'name' => $name,
                'store_type_id' => $store_type_id
            ]);

            event(new GroceryListChangedEvent($list));
        }

    }


    public function sync_edited_lists($lists, $user_id){
        foreach($lists as $list){
            $list = (object)$list;

            $list_id = $list->id;
            $categories = $list->categories;

            $list = GroceryList::where([ ['user_id', $user_id], ['id', $list_id] ])->first();

            if(!is_null($list)){
                GroceryListItem::where('list_id', $list_id)->delete();

                foreach($categories as $category){
                    $category = (object)$category;

                    $category_id = $category->id;

                    foreach($category->items as $list_item){
                        $list_item = (object)$list_item;

                        $product_id = $list_item->product_id;
                        $quantity = $list_item->quantity;
                        $ticked_off = (bool)$list_item->ticked_off;
                        $total_price = $this->item_price($product_id, $quantity);

                        GroceryListItem::insertOrIgnore(
                            [
                                'list_id' => $list_id, 
                                'product_id' =>  $product_id,
                                'parent_category_id' => $category_id, 
                                'quantity' => $quantity,
                                'ticked_off' =>  $ticked_off,
                                'total_price' => $total_price
                            ]
                        );
                    }
                }

                event(new GroceryListChangedEvent($list));
                
            }
        }
    }



    public function reset($list_id, $user_id){
        $list = GroceryList::where([ ['id',$list_id], ['user_id', $user_id] ]);

        if($list->exists()){
            GroceryListItem::where([['list_id', $list_id]])
            ->update([
                'ticked_off' => 0
            ]);

            $list->update(['status' => 'Not Started']);
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
    public function recent_items($user_id, $store_type_id){
        
        $product = new Product();

        return GroceryList::where('user_id', $user_id)
        ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->join('grocery_list_items','grocery_list_items.list_id','grocery_lists.id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->orderBy('grocery_lists.updated_at', 'DESC')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->where('products.store_type_id', $store_type_id)
        ->limit(15)->groupBy('category_products.product_id')->withCasts($product->casts)->get();
    }

    public function lists_progress($user_id, $store_type_id){
        return GroceryList::where([ ['user_id', $user_id],['store_type_id', $store_type_id] ])
        ->orderByRaw('(ticked_off_items/ total_items) DESC, `grocery_lists`.`updated_at` DESC')
        ->limit(4)->get();
    }
}
?>