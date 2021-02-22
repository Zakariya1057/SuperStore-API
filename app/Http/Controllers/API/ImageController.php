<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SanitizeService;
use App\Traits\ImageTrait;

class ImageController extends Controller {

    use ImageTrait;

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }
    
    public function show($type,$name){
        
        $type = $this->sanitize_service->sanitizeField($type);
        $name = $this->sanitize_service->sanitizeField($name);
        
        $image = $this->get_image($name, $type);

        return response($image, 200)->header('Content-Type', 'image/gif');

    }

    private function image_name($id, $size,$type){
        return "$type/{$id}_{$size}.jpg";
    }
}
