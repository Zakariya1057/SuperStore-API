<?php

namespace App\Console\Commands;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SaleExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sale:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all expired product sales from database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch all expired promotions, get all matching products remove product_id and then delete them
        $this->info('Fetching All Expired Sales');

        $products = Product::whereDate('sale_ends_at', '<', Carbon::now())->get();

        $product_ids = [];
        $products_count = count($products);

        $this->info($products_count . ' Products with expired sales');

        foreach($products as $product){
            $product_id = $product->id;
            $name = $product->name;
            $ends_at = $product->sale_ends_at;

            $price = $product->price;
            $old_price = $product->old_price;

            $this->info("Expired Product Sale Found: $ends_at [$product_id] $name - $price -> $old_price");

            $product_ids[] = $product_id;

            Product::where('id', $product_id)->update([
                'price' => $old_price, 
                'old_price' => null,
                'sale_ends_at' => null,
                'is_on_sale' => null
            ]);
        }

        $this->info('Finding Grocery Lists Containing Product');

        $list_ids = GroceryListItem::whereIn('product_id', $product_ids)->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');

        $lists = GroceryList::whereIn('id', $list_ids)->get();

        foreach($lists as $list){
            $this->info('List Found Using Product: ' . $list->id);
            event(new GroceryListChangedEvent($list));
        }

        if($products_count > 0){
            Artisan::call('cache:home');
        }
        
    }

}
