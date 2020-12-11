<?php

namespace App\Http\Controllers\API;

use Aws\S3\S3Client;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Traits\SanitizeTrait;
use Illuminate\Support\Facades\Redis;

class ImageController extends Controller {

    use SanitizeTrait;

    public function show($type,$name){
        
        $type = $this->sanitizeField($type);
        $name = $this->sanitizeField($name);
        
        $cache_key = "image_{$type}_{$name}";

        if(Redis::get($cache_key)){
            $image = base64_decode(Redis::get($cache_key));
        } else {

            try {
                $aws_config = (object)config('aws');

                $s3 = new S3Client([
                    'version' => $aws_config->version,
                    'region'  => $aws_config->region,
                    'credentials' => $aws_config->credentials
                ]);	
                    
                // Get the object.
                $result = $s3->getObject([
                    'Bucket' => $aws_config->bucket,
                    'Key'    => "$type/$name"
                ]);
    
                $image = $result['Body'];

                Redis::set($cache_key, base64_encode($image));
                Redis::expire($cache_key, 604800);

            } catch(Exception $e){
                $image = file_get_contents(__DIR__.'/../../../../public/img/no_image.png');
            }

        }

        return response($image, 200)->header('Content-Type', 'image/gif');

    }

    private function image_name($id, $size,$type){
        return "$type/{$id}_{$size}.jpg";
    }
}
