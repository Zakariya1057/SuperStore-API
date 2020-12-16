<?php

namespace App\Traits;

use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait ImageTrait {

    public function get_image($name,$type,$ignore_cache=false){

        $cache_key = "image_{$type}_{$name}";

        if(!$ignore_cache && Redis::get($cache_key)){
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
                $image = file_get_contents(__DIR__.'/../../public/img/no_image.png');
            }

        }

        return $image;

    }

}

?>