<?php

namespace App\Services;

use App\Events\GroceryListChangedEvent;
use App\Models\CategoryProduct;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use Exception;
use Illuminate\Support\Facades\Log;

class ListItemService extends ListSharedService {
    
    public function create($list_id, $data){
        $product_id = $data['product_id'];

        $parent_category_id = CategoryProduct::where('product_id', $product_id)->select('parent_category_id')->first()->parent_category_id;
       
        $quantity = $data['quantity'] ?? 1;
        $ticked_off = strtolower($data['ticked_off'] ?? 'false') == 'true' ? 1 : 0;

        $list = GroceryList::where('id', $list_id)->first();

        $total_price = $this->item_price($product_id, $quantity);

        if($list){
            GroceryListItem::updateOrCreate(
                [
                    'list_id' => $list_id, 
                    'product_id' =>  $product_id
                ],
    
                [
                    'parent_category_id' => $parent_category_id, 
                    'quantity' => $quantity,
                    'ticked_off' =>  $ticked_off,
                    'total_price' => $total_price
                ]
            );
        } else {
            throw new Exception('No list found.', 409);
        }

        event(new GroceryListChangedEvent($list));
    }

    public function update($list_id, $data, $user_id){

        $list = GroceryList::where([ [ 'id',$list_id], ['user_id', $user_id] ])->first();

        if($list){

            $quantity = $data['quantity'];

            if($quantity == 0){
                GroceryListItem::where([['list_id',$list_id],['product_id', $data['product_id']]])->delete();
            } else {

                $total_price = $this->item_price($data['product_id'], $data['quantity']);
                $ticked_off = strtolower($data['ticked_off']) == 'true' ? 1 : 0;
    
                GroceryListItem::where([['list_id',$list_id],['product_id', $data['product_id']]])
                ->update([
                    'quantity' => $quantity,
                    'ticked_off' => $ticked_off,
                    'total_price' => $total_price
                ]);

            }

        }

        event(new GroceryListChangedEvent($list));

    }

    public function delete(int $list_id, int $product_id, int $user_id){
        GroceryListItem::where([ ['list_id',$list_id], ['product_id',$product_id, ['user_id', $user_id]] ])->delete();
        $list = GroceryList::where([ [ 'id',$list_id], ['user_id', $user_id] ])->first();
        event(new GroceryListChangedEvent($list));
    }
}
?>