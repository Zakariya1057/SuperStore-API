<?php

namespace App\Services\Store;

use App\Models\Flyer;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Services\Storage\StorageService;

class FlyerService {
    private $storage_service;

    public function __construct(StorageService $storage_service){
        $this->storage_service = $storage_service;
    }

    public function show($name){
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

    public function all($store_id){
        // Get all flyers for store
        return Flyer::where('store_id', $store_id)->get();
    }
}

?>