<?php

namespace App\Services;

use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use App\Casts\PromotionCalculator;
use App\Models\CategoryProduct;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListSharedService {

    ////////////////////////////////////////////    SELECT List    //////////////////////////////////////////// 

    public function show_list($list_id, $user_id){

        $product = new Product();

        $list = GroceryList::where([ ['id', $list_id],['user_id', $user_id] ])->first();

        if(!$list){
            throw new Exception('No list found for user.', 404);
        }

        $product = new Product();

        $casts = $product->casts ?? [];
        $casts['promotion'] = PromotionCalculator::class;

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
            'category_aisles.name as aisle_name',
            'products.weight as weight',
            'products.small_image as small_image',
            'products.large_image as large_image',
            'grocery_list_items.ticked_off as ticked_off',
            'promotions.id as promotion_id',
            'promotions.name as promotion',
            'promotions.store_type_id'
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

        $list->categories = $this->group_by_categories($items);

        return $list;
    }

    private function group_by_categories($items){
        $categories = [];

        foreach($items as $item){

            $category_id = $item->category_id;
            $category_name = html_entity_decode($item->category_name, ENT_QUOTES);
            $aisle_name = $item->aisle_name;
            
            unset($item->category_name);
            unset($item->aisle_name);
            unset($item->category_id);
            
            if(key_exists($category_name, $categories)){
                $categories[$category_name]['items'][] = $item;
            } else {
                $categories[$category_name] = [ 
                    'id' => $category_id,
                    'name' =>  $category_name,
                    'aisle_name' => $aisle_name,
                    'items' => [$item]
                ];
            }
            
        }

        return array_values($categories);

    }

    public function item_price($product_id,$quantity=1){

        $product = Product::where('products.id',$product_id)
        ->select('products.price', 'promotions.id as promotion_id','promotions.name as promotion', 'promotions.store_type_id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->withCasts(['promotion' => PromotionCalculator::class])
        ->get()->first();
    
        $price = $product->price;
        $total = 0;

        if($quantity == 0){
            return $total;
        }

        if(!is_null($product->promotion)){
            $promotion_details = (object)$product->promotion;
            $remainder = ($quantity % $promotion_details->quantity);
            $goes_into_fully = floor($quantity / $promotion_details->quantity);

            if($quantity < $promotion_details->quantity){
                $total = $quantity * $price;   
            } else {

                if( !is_null($promotion_details->for_quantity)){
                    $total = ( $goes_into_fully * ( $promotion_details->for_quantity * $price)) + ($remainder * $price);
                } else {
                    $total = ($goes_into_fully * $promotion_details->price) + ($remainder * $price);
                }
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
        ->leftJoin('promotions','promotions.id','products.promotion_id')
        ->where('list_id',$list->id)
        ->select(
            'products.id as product_id',
            'grocery_list_items.quantity as product_quantity',
            'products.price as product_price',
            'grocery_list_items.total_price',
            'grocery_list_items.ticked_off',
            'promotions.id as promotion_id',
            'promotions.name as promotion',
            'promotions.store_type_id'
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

        return $data;

    }

    private function group_list_items($items): array{

        $promotions = [];
        $total_price = 0;
        $ticked_off_items = 0;

        foreach($items as $item){

            if(!is_null($item->promotion)){
                $promotion = (object)$item->promotion;

                if(key_exists($promotion->id,$promotions)){
                    $promotions[$promotion->id]['products'][] = $item;
                } else {
                    $promotions[$promotion->id] = [
                        'details' => $promotion,
                        'products' => [$item],
                    ];
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

    public function update_list_items($list_id, $items,$mode){
        // Delete all list items, create new ones
        $mode = strtolower($mode);

        DB::beginTransaction();

        try {

            if($mode == 'overwrite'){
                $this->overwrite_list_items($list_id, $items);
            } else if ($mode == 'append') {
                $this->append_list_items($list_id, $items);
            } else {
                Log::error('Unknown Update List Type Mode: '.$mode);
            }
    
        } catch(Exception $e) {
            Log::error('Grocery List Update. Sync Error: ' . $e->getMessage());
            DB::rollBack();
        }

        DB::commit();

    }

    private function append_list_items($list_id, $items){
        foreach($items as $item){
        
            $quantity = $item['quantity'] ?? 1;
            $ticked_off = strtolower($item['ticked_off'] ?? 'false') == 'true' ? 1 : 0;
            $product_id = $item['product_id'];
            $total_price = $this->item_price($product_id, $quantity);

            $parent_category_id = CategoryProduct::where('product_id', $product_id)->select('parent_category_id')->first()->parent_category_id;
            
            GroceryListItem::insertOrIgnore(
                [
                    'list_id' => $list_id, 
                    'product_id' =>  $product_id,
                    'parent_category_id' => $parent_category_id, 
                    'quantity' => $quantity,
                    'ticked_off' =>  $ticked_off,
                    'total_price' => $total_price
                ]
            );

        }
    }

    private function overwrite_list_items($list_id, $items){
        GroceryListItem::where('list_id', $list_id)->delete();
    
        foreach($items as $item){

            $quantity = $item['quantity'] ?? 1;
            $ticked_off = strtolower($item['ticked_off'] ?? 'false') == 'true' ? 1 : 0;
            $product_id = $item['product_id'];
            $total_price = $this->item_price($product_id, $quantity);

            $parent_category_id = CategoryProduct::where('product_id', $product_id)->select('parent_category_id')->first()->parent_category_id;
            
            GroceryListItem::create(
                [
                    'list_id' => $list_id, 
                    'product_id' =>  $product_id,
                    'parent_category_id' => $parent_category_id, 
                    'quantity' => $quantity,
                    'ticked_off' =>  $ticked_off,
                    'total_price' => $total_price
                ]
            );

        }
    }

    ////////////////////////////////////////////    Update List    //////////////////////////////////////////// 

}
?>