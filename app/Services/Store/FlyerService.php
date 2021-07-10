<?php

namespace App\Services\Store;

use App\Models\Flyer;
use App\Models\FlyerProduct;
use App\Models\Product;
use App\Services\Sanitize\SanitizeService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Services\Storage\StorageService;

class FlyerService {
    private $sanitize_service, $storage_service;

    public function __construct(SanitizeService $sanitize_service, StorageService $storage_service){
        $this->sanitize_service = $sanitize_service;
        $this->storage_service = $storage_service;
    }

    public function show(string $name){
        $name = $this->sanitize_service->sanitizeField($name);

        $cache_key = "flyers_{$name}";
        $path = 'flyers/' . $name;

        try {
            $flyer = $this->storage_service->get($path, 'pdf');
            Redis::set($cache_key, base64_encode($flyer));
        } catch(Exception $e){
            Log::error("Flyer not found: Name: {$name}", ['error' => $e]);
            $flyer = file_get_contents(__DIR__.'/../../public/img/no_image.png');
        }

        return $flyer;
    }

    public function all(int $store_id){
        // Get all flyers for store
        $store_id = $this->sanitize_service->sanitizeField($store_id);
        return Flyer::where('store_id', $store_id)->get();
    }

    public function products(int $flyer_id){
        $flyer_id = $this->sanitize_service->sanitizeField($flyer_id);
        $flyer = Flyer::where('flyers.id', $flyer_id)
        ->join('store_locations', 'store_locations.store_id', 'flyers.store_id')
        ->join('stores', 'stores.id', 'flyers.store_id')
        ->first();

        if($flyer){
            $product = new Product();
            $casts = $product->casts;
    
            return FlyerProduct::where('flyer_id', $flyer_id)
            ->select(
                'products.*',
    
                'product_prices.price', 
                'product_prices.old_price',
                'product_prices.is_on_sale', 
                'product_prices.sale_ends_at', 
                'product_prices.promotion_id', 
                'product_prices.region_id',
                'product_prices.supermarket_chain_id',
            )
            ->join('products', 'products.id', 'flyer_products.product_id')
            ->join('product_prices','product_prices.product_id','products.id')
            ->where([ ['product_prices.region_id', $flyer->region_id], ['product_prices.supermarket_chain_id', $flyer->supermarket_chain_id] ])
            ->withCasts($casts)->get();

        } else {
            Log::error('No Flyer Region Found: ' . $flyer_id);
            return [];
        }

    }
    
}

?>