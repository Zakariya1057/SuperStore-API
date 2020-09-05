<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Cache;

class ImageController extends Controller
{
    public function show($type,$name){

        // $image = Cache::remember("image_{$type}_{$name}", 86400, function () use($type,$name) {

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

        // });

        return response($image, 200)->header('Content-Type', 'image/gif');

    }

    private function image_name($id, $size,$type){
        return "$type/{$id}_{$size}.jpg";
    }
}
