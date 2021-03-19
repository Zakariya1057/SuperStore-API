<?php

namespace App\Console\Commands;

use App\Models\GrandParentCategory;
use App\Models\StoreType;
use App\Services\CategoryService;
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

            $this->info('Starting Caching Categories For Store: ' . $store_type->name);

            $store_type_id = $store_type->id;

            $grand_parent_categories = GrandParentCategory::where('store_type_id', $store_type_id)->get();

            $parent_categories_details = [];
    
            // Top cache, store all grand parent categories and their child categories
            foreach($grand_parent_categories as $grand_parent_category){
                $parent_categories = $grand_parent_category->parent_categories;
                $parent_categories_details[$grand_parent_category->name] = $parent_categories;
            }
    
            Cache::put('categories_'.$store_type_id, $grand_parent_categories);
    
            // Cache all parent categories, with their products
            foreach($parent_categories_details as $grand_parent_category_name => $child_categories){
                $this->info('Caching Categories For: '.$grand_parent_category_name);
    
                foreach($child_categories as $child_category){
                    $this->info('Caching Product Categories For: '.$child_category->name);
                    $product_categories = $this->category_service->grocery_products($child_category->id);
                    Cache::put('category_products_'.$child_category->id, $product_categories);
                }
            }

            $this->info('Complete Caching Categories For Store: ' . $store_type->name);
        }

        $this->info('Daily Grocery Cache Complete');

    }

}