<?php

namespace App\Http\Controllers\API;

use Aws\S3\S3Client;
use App\Http\Controllers\Controller;
use App\Traits\ImageTrait;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Traits\SanitizeTrait;
use Illuminate\Support\Facades\Redis;

class ImageController extends Controller {

    use SanitizeTrait;
    use ImageTrait;

    public function show($type,$name){
        
        $type = $this->sanitizeField($type);
        $name = $this->sanitizeField($name);
        
        $image = $this->get_image($name, $type);

        return response($image, 200)->header('Content-Type', 'image/gif');

    }

    private function image_name($id, $size,$type){
        return "$type/{$id}_{$size}.jpg";
    }
}
