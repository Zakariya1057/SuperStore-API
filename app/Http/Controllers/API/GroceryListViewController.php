<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\GroceryList;
use App\GroceryListItem;
use App\Product;
use App\Traits\GroceryListTrait;
use Illuminate\Http\Request;

class GroceryListViewController extends Controller
{

    use GroceryListTrait;

    public function create($list_id, Request $request){

        $validated_data = $request->validate([
            'data.product_id' => 'required'
        ]);

        $data = $validated_data['data'];
        $product_id = $data['product_id'];

        $parent_category_id = Product::where('products.id', $product_id)
        ->select('parent_categories.id')
        ->join('child_categories', 'child_categories.id','products.parent_category_id')
        ->join('parent_categories', 'parent_categories.id','child_categories.parent_category_id')
        ->first()->id;
       
        $quantity = 1;

        $grocery = new GroceryListItem();
        $list = GroceryList::where('id', $list_id)->first();

        if($list){
            if($grocery->where([['list_id',$list_id], ['product_id', $product_id]])->doesntExist()){

                $grocery->list_id = $list_id;
                $grocery->product_id = $product_id;
                $grocery->parent_category_id = $parent_category_id;
                $grocery->quantity = $quantity;

                $grocery->total_price = $this->item_price($product_id, $quantity);
        
                $grocery->save();
                
                $this->update_list($list);
            } else {
                return response()->json(['data' => ['error' => 'Duplicate Product Found In Database']], 409);
            }
        } else {
            return response()->json(['data' => ['error' => 'No List Found']], 404);
        }

        // Set off message queue to update list total.
        // For Now just update it here.
        return response()->json(['data' => ['status' => 'success']]);

    }
    
    public function update($list_id, Request $request){
        // Item ticked off, or quantity changed
        $validated_data = $request->validate([
            'data.product_id' => 'required',
            'data.quantity' => 'required',
            'data.ticked_off' => 'required'
        ]);

        $data = $validated_data['data'];

        $user_id = $request->user()->id;

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

        // If quantity change, update list total with job
        $this->update_list($list);

        // If all products ticked off, then change status to complete
        return response()->json(['data' => ['status' => 'success']]);
    }

    public function delete($list_id, Request $request){
        $validated_data = $request->validate([
            'data.product_id' => 'required',
        ]);

        $product_id = $validated_data['data']['product_id'];

        $user_id = $request->user()->id;

        GroceryListItem::where([ ['list_id',$list_id], ['product_id',$product_id, ['user_id', $user_id]] ])->delete();

        $list = GroceryList::where([ [ 'id',$list_id], ['user_id', $user_id] ])->first();

        $this->update_list($list);

        return response()->json(['data' => ['status' => 'success']]);
    }

}
