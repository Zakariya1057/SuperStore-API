<?php

namespace App\Console\Commands;

use App\Services\GroceryService;
use App\Services\PromotionService;
use App\Services\StoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
class CacheHome extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:home';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches home for decreasing loading speed.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $store_service, $grocery_service, $promotion_service;

    function __construct(GroceryService $grocery_service, StoreService $store_service, PromotionService $promotion_service){
        parent::__construct();
        $this->store_service = $store_service;
        $this->promotion_service = $promotion_service;
        $this->grocery_service = $grocery_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('---- Weekly Home Cache Start ----');

        $cache_key = 'home_page';

        $featured_items = $this->grocery_service->featured_items();
        $stores = $this->store_service->stores_by_type(1,false);
        $categories = $this->grocery_service->home_categories();
        $promotions = $this->promotion_service->store_promotions(1);

        $data = [
            'stores' => $stores,
            'featured' => $featured_items,
            'promotions' => $promotions,
            'categories' => $categories,
        ];

        Redis::set($cache_key, json_encode($data));
        Redis::expire($cache_key, 604800);

        $this->info('---- Weekly Home Cache Complete ----');
        
    }
}