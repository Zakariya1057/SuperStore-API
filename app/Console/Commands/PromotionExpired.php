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

class PromotionExpired extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotion:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all expired promotions from database.';

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
        $this->info('Fetching All Expired Promotions');

        $promotions = Promotion::whereDate('ends_at', '<', Carbon::now())->get();
        $promotions_count = count($promotions);

        $this->info( $promotions_count . ' Expired Promotions Found');

        foreach($promotions as $promotion){
            $id = $promotion->id;
            $name = $promotion->name;
            $ends_at = $promotion->ends_at;

            $this->info("Expired Promotion Found: [$id] $name - $ends_at");

            $this->info('Removing Product Promotions Linked');

            $product_ids = Product::where('promotion_id', $id)->pluck('id');

            $this->info('Finding Grocery Lists Containing Product');

            $list_ids = GroceryListItem::whereIn('product_id', $product_ids)->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');

            $lists =  GroceryList::whereIn('id', $list_ids)->get();

            foreach($lists as $list){
                $this->info('List Found Using Product: ' . $list->id);
                event(new GroceryListChangedEvent($list));
            }

            Product::where('promotion_id', $id)->update(['promotion_id' => null]);
            Promotion::where('id', $id)->delete();

        }

        if($promotions_count > 0){
            Artisan::call('cache:home');
        }

    }

}
