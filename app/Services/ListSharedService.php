<?php

namespace App\Services;

use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListSharedService {

    private $promotion_service;

    function __construct()
    {
        $this->promotion_service = new PromotionService();
    }
    ////////////////////////////////////////////    SELECT List    //////////////////////////////////////////// 

    public function show_list($list_id, $user_id){

        $product = new Product();

        $list = GroceryList::where([ ['id', $list_id],['user_id', $user_id] ])->first();

        if(!$list){
            throw new Exception('No list found for user.', 404);
        }

        $product = new Product();
        $casts = $product->casts;

        $items = GroceryListItem::where([ ['list_id', $list->id] ])
        ->select([
            'grocery_list_items.id as id',
            'grocery_list_items.product_id as product_id',
            'parent_categories.name as category_name',
            'parent_categories.id as category_id',
            'products.name as name',
            'grocery_list_items.total_price as total_price',
            'products.price as price',
            'products.currency as currency',
            'grocery_list_items.quantity as quantity',
            'products.weight as weight',
            'products.small_image as small_image',
            'products.large_image as large_image',
            'grocery_list_items.ticked_off as ticked_off',

            'promotions.id as promotion_id',
            'promotions.store_type_id as promotion_store_type_id',
            'promotions.name as promotion_name',
            'promotions.quantity as promotion_quantity',
            'promotions.price as promotion_price',
            'promotions.for_quantity as promotion_for_quantity',

            'promotions.minimum as promotion_minimum',
            'promotions.maximum as promotion_maximum',

            'promotions.expires as promotion_expires',
            'promotions.starts_at as promotion_starts_at',
            'promotions.ends_at as promotion_ends_at',
        ])
        ->join('parent_categories', 'parent_categories.id','=','grocery_list_items.parent_category_id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->orderBy('grocery_list_items.id','ASC')
        ->withCasts(
            $casts
        )
        ->get();

        $list->categories = $this->group_by_categories($items);

        return $list;
    }

    private function group_by_categories($items){
        $categories = [];

        foreach($items as $item){

            $this->promotion_service->set_product_promotion($item);

            $category_id = $item->category_id;
            $category_name = html_entity_decode($item->category_name, ENT_QUOTES);
            
            unset($item->category_name);
            unset($item->category_id);
            
            if(key_exists($category_name, $categories)){
                $categories[$category_name]['items'][] = $item;
            } else {
                $categories[$category_name] = [ 
                    'id' => $category_id,
                    'name' =>  $category_name,
                    'aisle_name' => '',
                    'items' => [$item]
                ];
            }
            
        }

        return array_values($categories);

    }

    public function item_price($product_id,$quantity=1){

        $product = Product::where('products.id',$product_id)->get()->first();
    
        $promotion = $product->promotion;

        $price = $product->price;

        // Check if sale expired, use old price instead of new
        if(!is_null($product->sale_ends_at)){
            if(Carbon::now() > $product->sale_ends_at){
                $price = $product->old_price;
            }
        }

        $total = 0;

        if($quantity == 0){
            return $total;
        }

        if(!is_null($promotion)){

            $promotion_expired = false;

            if(!is_null($promotion->ends_at)){
                if(Carbon::now() > $promotion->ends_at){
                    $promotion_expired = true;
                }
            }

            if(!$promotion_expired){

                if(!is_null($promotion->minimum)){
                    $minimum = $promotion->minimum;
                    $promotion_price = $promotion->price;
    
                    if($quantity >= $minimum){
                        $total = $quantity * $promotion_price;
                    } else {
                        $total = $quantity * $price;
                    }
    
                } else {
                    $remainder = ($quantity % $promotion->quantity);
                    $goes_into_fully = floor($quantity / $promotion->quantity);
    
                    if($quantity < $promotion->quantity){
                        $total = $quantity * $price;   
                    } else {
    
                        if( !is_null($promotion->for_quantity)){
                            $total = ( $goes_into_fully * ( $promotion->for_quantity * $price)) + ($remainder * $price);
                        } else {
                            $total = ($goes_into_fully * $promotion->price) + ($remainder * $price);
                        }
                    }
                }

            } else {
                $total = $quantity * $price;
            }

        } else {
            $total = $quantity * $price;
        }
        
        return $total;

    }

    ////////////////////////////////////////////    SELECT List    //////////////////////////////////////////// 



    ////////////////////////////////////////////    UPDATE List    //////////////////////////////////////////// 

    public function update_list(GroceryList $list){
        
        $product = new Product();
        $casts = $product->casts;

        $items =  GroceryListItem::
        join('products','products.id','grocery_list_items.product_id')
        ->where('list_id',$list->id)
        ->select(
            'products.id as product_id',
            'grocery_list_items.quantity as product_quantity',
            'products.price as product_price',
            // 'grocery_list_items.total_price',
            'grocery_list_items.ticked_off',
        )
        ->withCasts($casts)
        ->get();

        $list_data = $this->group_list_items($items);

        $promotions = $list_data['promotions'];
        $total_price = $list_data['total_price'];
        $ticked_off_items = $list_data['ticked_off_items'];

        $update = [];

        $price_data = $this->parse_promotion_data($promotions, $total_price);

        $update['old_total_price'] = $price_data['old_total_price'];
        $update['total_price'] = $price_data['total_price'];

        $update['status'] = $this->get_list_status(count($items), $ticked_off_items);

        DB::transaction(function () use($list, $update){
            GroceryList::where('id',$list->id)->update($update);
        }, 5);
        
    }

    private function parse_promotion_data($promotions, $total_price): array {

        $new_total_price = 0;
        $data = ['total_price' => $total_price, 'old_total_price' => null];

        // For All Products Within Promotion Group
        foreach($promotions as $promotion){
            $promotion = (object)$promotion;

            $promotion_details = $promotion->details;
            $products = $promotion->products;

            $total_quantity = 0;

            foreach($products as $product){
                $total_quantity += $product->product_quantity;
            }

            if(!is_null($promotion_details->quantity)){

                $quantity = $promotion_details->quantity;

                $new_total = 0;

                if($total_quantity >= $quantity){

                    $highest_price = 0;
                    $previous_total_price = 0;
    
                    // Get the most expensive item
                    foreach($products as $product){
                        $previous_total_price = $previous_total_price + $product->total_price;
    
                        if($product->product_price > $highest_price){
                            $highest_price = $product->product_price;
                        }
                    }
                    
                    $remainder = ($total_quantity % $promotion_details->quantity);
                    $goes_into_fully = floor($total_quantity / $promotion_details->quantity);
    
                    if( !is_null($promotion_details->for_quantity)){
                        $new_total = ( $goes_into_fully * ( $promotion_details->for_quantity * $highest_price)) + ($remainder * $highest_price);
                    } else {
                        $new_total = ($goes_into_fully * $promotion_details->price) + ($remainder * $highest_price);
                    }
    
                    $new_total_price = ($total_price - $previous_total_price) + $new_total;
    
                    if($new_total < $previous_total_price){
                        $data['old_total_price'] = $total_price;
                        $data['total_price'] = $new_total_price;
                    }
    
                }

            }

        }

        return $data;

    }

    private function group_list_items($items): array{

        $promotions = [];
        $total_price = 0;
        $ticked_off_items = 0;

        foreach($items as $item){
            $item->total_price = $this->item_price($item->product_id, $item->product_quantity);

            $promotion = $item->promotion;

            if(!is_null($promotion)){

                $promotion_expired = false;

                if(!is_null($promotion->ends_at)){
                    if(Carbon::now() > $promotion->ends_at){
                        $promotion_expired = true;
                    }
                }
                
                if(!$promotion_expired){
                    if(key_exists($promotion->id,$promotions)){
                        $promotions[$promotion->id]['products'][] = $item;
                    } else {
                        $promotions[$promotion->id] = [
                            'details' => $promotion,
                            'products' => [$item],
                        ];
                    }
                }
            }

            $total_price += $item->total_price;

            if($item->ticked_off == 1){
                $ticked_off_items++;
            }
        }

        return ['promotions' => $promotions, 'total_price' => $total_price, 'ticked_off_items' => $ticked_off_items];
    }

    private function get_list_status(int $total_items, int $ticked_off_items): string { 
        if($ticked_off_items == $total_items){
            return 'Completed';
        } elseif($ticked_off_items > 0){
            return 'In Progress';
        } else {
            return 'Not Started';
        }
    }

    ////////////////////////////////////////////    Update List    //////////////////////////////////////////// 

}
?>