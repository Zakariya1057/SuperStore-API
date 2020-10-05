<?php

namespace App\Traits;

use App\GroceryList;
use App\GroceryListItem;
use App\Product;
use App\Casts\PromotionCalculator;
use Illuminate\Support\Facades\Log;

trait GroceryListTrait {

    protected function update_list($list){
        // Get all products for list.
        // Check if any promotion
        // Multiple quantity with price
        
        if($list instanceOf GroceryList){
            $items =  GroceryListItem::where('list_id',$list->id)->get();
            $status = 'Not Started';

            $total_price = 0;

            $total_items = count($items);
            $ticked_off_items = 0;

            foreach($items as $item){
                $total_price += $item->total_price;

                if($item->ticked_off == 1){
                    $ticked_off_items++;
                }
            }

            if($ticked_off_items == $total_items){
                $status = 'Completed';
            } elseif($ticked_off_items > 0){
                $status = 'In Progress';
            }

            GroceryList::where('id',$list->id)->update(['total_price' => $total_price, 'status' => $status]);
        }

    }

    protected function show_list($list_id, $user_id){

        $product = new Product();

        $list = GroceryList::where([ [ 'id', $list_id],['user_id', $user_id] ])->first();

        if(!$list){
            return response()->json(['data' => ['error' => 'No List Found For User']], 404);
        }

        $product = new Product();

        $casts = $product->casts ?? [];
        $casts['discount'] = PromotionCalculator::class;

        $list_items = GroceryListItem::where([ ['list_id', $list->id] ])
        ->select([
            'grocery_list_items.id as id',
            'grocery_list_items.product_id as product_id',
            'parent_categories.name as category_name',
            'parent_categories.id as category_id',
            'products.name as name',
            'grocery_list_items.total_price as total_price',
            'products.price as price',
            'grocery_list_items.quantity as quantity',
            'category_aisles.name as aisle_name',
            'products.weight as weight',
            'products.small_image as small_image',
            'products.large_image as large_image',
            'grocery_list_items.ticked_off as ticked_off',
            'promotions.name as discount'
        ])
        ->join('parent_categories', 'parent_categories.id','=','grocery_list_items.parent_category_id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->leftJoin('category_aisles', function ($join) use($list) {
            $join->on('category_aisles.category_id','=','grocery_list_items.parent_category_id')
                 ->where('category_aisles.store_id',$list->store_id);
        })
        ->orderBy('grocery_list_items.parent_category_id','ASC')
        ->withCasts(
            $casts
        )
        ->get();

        $categories = [];

        foreach($list_items as $list_item){

            $category_id = $list_item->category_id;
            $category_name = html_entity_decode($list_item->category_name, ENT_QUOTES);
            $aisle_name = $list_item->aisle_name;
            
            unset($list_item->category_name);
            unset($list_item->aisle_name);
            unset($list_item->category_id);
            
            if(key_exists($category_name, $categories)){
                $categories[$category_name]['items'][] = $list_item;
            } else {
                $categories[$category_name] = [ 
                    'id' => $category_id,
                    'name' =>  $category_name,
                    'aisle_name' => $aisle_name,
                    'items' => [$list_item]
                ];
            }
            
        }

        $list->categories = array_values($categories);

        return $list;
    }

    protected function item_price($product_id,$quantity=1){
        $product = Product::where('products.id',$product_id)->leftJoin('promotions', 'promotions.id','=','products.promotion_id')->select('products.price','promotions.name as discount')->withCasts(['discount' => PromotionCalculator::class])->get()->first();
    
        $price = $product->price;
        $total = 0;

        if($quantity == 0){
            return $total;
        }

        if(!is_null($product->discount)){
            $discount_details = (object)$product->discount;
            $remainder = ($quantity % $discount_details->quantity);
            $goes_into_fully = round($quantity / $discount_details->quantity);

            if($quantity < $discount_details->quantity){
                $total = $quantity * $price;   
            } else {

                if( !is_null($discount_details->for_quantity)){
                    $total = ( $goes_into_fully * ( $discount_details->for_quantity * $price)) + ($remainder * $price);
                } else {
                    $total = ($goes_into_fully * $discount_details->price) + ($remainder * $price);
                }
            }

        } else {
            $total = $quantity * $price;
        }
        
        return $total;

    }

}

?>