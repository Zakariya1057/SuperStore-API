<?php

namespace App\Services\Image;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Services\Storage\StorageService;

class ImageService {

    private $storage_service;

    public function __construct(StorageService $storage_service){
        $this->storage_service = $storage_service;
    }

    public function get_image($name,$type,$ignore_cache=false){
        $cache_key = "image_{$type}_{$name}";

        if(!$ignore_cache && Redis::get($cache_key)){
            $image = base64_decode(Redis::get($cache_key));
        } else {
            try {
                $path = "$type/$name";
                $image = $this->storage_service->get($path);

                Redis::set($cache_key, base64_encode($image));
            } catch(Exception $e){
                Log::error("Image not found: Name: {$name}, Type: {$type}", ['error' => $e]);
                $image = file_get_contents(__DIR__.'/../../../public/img/no_image.png');
            }
        }

        return $image;

    }

}
?>