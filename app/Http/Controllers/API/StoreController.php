<?php

namespace App\Http\Controllers\API;

use App\Models\Store;
use App\Traits\StoreTrait;
use Exception;
use App\Casts\Image;
use App\Http\Controllers\Controller;
use App\Services\SanitizeService;

class StoreController extends Controller {
    
    use StoreTrait;

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function show($store_id)
    {

        $store_id = $this->sanitize_service->sanitizeField($store_id);

        $store = Store::select('stores.*', 'store_types.large_logo', 'store_types.small_logo')
        ->where('stores.id', $store_id)
        ->join('store_types', 'store_types.id', '=', 'stores.store_type_id')
        ->withCasts([
            'large_logo' => Image::class,
            'small_logo' => Image::class,
        ])
        ->first();

        if($store){
            $store->location;
            $store->opening_hours;
            $store->facilities;
        } else {
            throw new Exception('No store found.', 404);
        }

        return $store;
    }

}
