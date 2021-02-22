<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use App\Services\SanitizeService;

class ImageController extends Controller {

    private $sanitize_service, $image_service;

    function __construct(SanitizeService $sanitize_service, ImageService $image_service){
        $this->sanitize_service = $sanitize_service;
        $this->image_service = $image_service;
    }
    
    public function show($type,$name){
        
        $type = $this->sanitize_service->sanitizeField($type);
        $name = $this->sanitize_service->sanitizeField($name);
        
        $image = $this->image_service->get_image($name, $type);

        return response($image, 200)->header('Content-Type', 'image/gif');
    }
}
