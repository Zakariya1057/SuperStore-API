<?php

namespace App\Console\Commands;

use App\Models\GrandParentCategory;
use App\Models\StoreType;
use App\Services\Category\CategoryService;
use App\Services\GroceryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheGroceries extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:groceries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches grocery categories and products.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $category_service;

    public function __construct(CategoryService $category_service)
    {
        parent::__construct();
        $this->category_service = $category_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        
        $this->info('Daily Grocery Cache Start');

        foreach(StoreType::get() as $store_type){

            $this->info("\n---- Starting Caching Categories For Store: " . $store_type->name);

            $store_type_id = $store_type->id;

            $grand_parent_categories = $this->category_service->grand_parent_categories($store_type_id);

            Cache::put('grand_parent_category_'.$store_type_id, $grand_parent_categories);

            foreach($grand_parent_categories as $grand_parent_category){
                $grand_parent_category_id = $grand_parent_category->id;
                $grand_parent_category_name = $grand_parent_category->name;
                
                $this->info("--- Start Caching Categories For Grand Parent Category: [$grand_parent_category_id] $grand_parent_category_name");

                foreach($grand_parent_category->parent_categories as $parent_category){
                    $parent_category_id = $parent_category->id;
                    $parent_category_name = $parent_category->name;
                    
                    $this->info("\n-- Start Caching Child Categories For Parent Category: [$parent_category_id] $parent_category_name");

                    $child_categories = $this->category_service->child_categories($parent_category_id);
    
                    Cache::put('child_category_'. $parent_category_id, $child_categories);

                    foreach($child_categories as $category){
                        $child_category_id = $category->id;
                        $child_category_name = $category->name;

                        $this->info("Caching Category Products For: [$child_category_id] $child_category_name");

                        $category_products = $this->category_service->category_products($child_category_id);

                        Cache::put('category_products_' . $child_category_id, $category_products);
                        
                    }

                    $this->info("-- Complete Caching Child Categories For Parent Category: [$parent_category_id] $parent_category_name");
                }

                $this->info("-- Complete Caching Categories For Grand Parent Category: [$parent_category_id] $parent_category_name");

            }

            $this->info('---- Complete Caching Categories For Store: ' . $store_type->name);
        }

        $this->info('Daily Grocery Cache Complete');

    }

}