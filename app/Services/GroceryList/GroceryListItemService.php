<?php

namespace App\Services\GroceryList;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use Exception;

class GroceryListItemService extends ListSharedService {
    
    public function create($list_id, $data){
        $product_id = $data['product_id'];
        $parent_category_id = $data['parent_category_id'];
       
        $quantity = $data['quantity'] ?? 1;

        $list = GroceryList::where('id', $list_id)->first();

        $total_price = $this->item_price($product_id, $quantity);

        if($list){

            $list_item = GroceryListItem::where([ ['list_id', $list_id], ['product_id', $product_id] ])->get()->first();

            if(is_null($list_item)){
                $list_item = GroceryListItem::create([
                    'product_id' => $product_id,
                    'list_id' => $list_id,

                    'parent_category_id' => $parent_category_id, 
                    'quantity' => $quantity,
                    'ticked_off' =>  false,
                    'total_price' => $total_price
                ]);
            }

            $list_item->product_id = (int)$product_id;

            $product_details = Product::where('id', $product_id)->first();

            if($product_details){
                $list_item->name = $product_details->name;
                $list_item->price = $product_details->price;
                $list_item->currency = $product_details->currency;
            }

        } else {
            throw new Exception('No list found.', 409);
        }

        event(new GroceryListChangedEvent($list));

        return $list_item;
    }

    public function update($list_id, $data, $user_id){

        $list = GroceryList::where([ [ 'id',$list_id], ['user_id', $user_id] ])->first();

        if($list){

            $product_id = $data['product_id'];
            $quantity = $data['quantity'];
            $ticked_off = $data['ticked_off'];

            if($quantity == 0){
                GroceryListItem::where([['list_id',$list_id],['product_id', $data['product_id']]])->delete();
            } else {

                $total_price = $this->item_price($product_id, $quantity);
    
                GroceryListItem::where([['list_id',$list_id],['product_id', $data['product_id']]])
                ->update([
                    'quantity' => $quantity,
                    'ticked_off' => $ticked_off,
                    'total_price' => $total_price
                ]);

            }
            
            event(new GroceryListChangedEvent($list));

        }

    }

    public function delete(int $list_id, int $product_id, int $user_id){
        GroceryListItem::where([ ['list_id',$list_id], ['product_id',$product_id, ['user_id', $user_id]] ])->delete();
        $list = GroceryList::where([ [ 'id',$list_id], ['user_id', $user_id] ])->first();
        event(new GroceryListChangedEvent($list));
    }
}
?>