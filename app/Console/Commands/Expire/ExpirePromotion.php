<?php

namespace App\Console\Commands\Expire;

use App\Events\GroceryListChangedEvent;
use App\Models\FlyerProduct;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\ProductPrice;
use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpirePromotion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:promotion';

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

        DB::beginTransaction();

        $promotions = Promotion::whereDate('ends_at', '<', Carbon::now())->get();
        $promotions_count = count($promotions);

        $this->info( $promotions_count . ' Expired Promotions Found');

        foreach($promotions as $promotion){
            $id = $promotion->id;
            $name = $promotion->name;
            $ends_at = $promotion->ends_at;

            $this->info("Expired Promotion Found: [$id] $name - $ends_at");

            $this->info('Removing Product Promotions Linked');

            $product_ids = ProductPrice::where('promotion_id', $id)->pluck('product_id');

            $this->info('Finding Grocery Lists Containing Product');

            $list_ids = GroceryListItem::whereIn('product_id', $product_ids)->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');

            $lists = GroceryList::whereIn('id', $list_ids)->get();

            foreach($lists as $list){
                $user = User::where('id', $list->user_id)->get()->first();
                Auth::login($user);

                $this->info('List Found Using Product: ' . $list->id);
                event(new GroceryListChangedEvent($list));
            }

            ProductPrice::where('promotion_id', $id)->update(['promotion_id' => null]);
            Promotion::where('id', $id)->delete();
            FlyerProduct::where('flyer_id', $id)->delete();
        }

        // Get all promotions without products
        $empty_promotions = Promotion::leftJoin('product_prices', 'promotions.id', 'product_prices.promotion_id')->where([ ['supermarket_chain_id', 2] ])->whereNull('product_id')->pluck('promotions.id');
        $this->info('Num Empty Promotions: ' . count($empty_promotions));
        Promotion::whereIn('id', $empty_promotions)->delete();
        FlyerProduct::whereIn('flyer_id', $empty_promotions)->delete();

        DB::commit();

        if($promotions_count > 0){
            Artisan::call('cache:home');
        }

    }

}
