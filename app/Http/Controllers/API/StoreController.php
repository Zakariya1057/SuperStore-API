<?php

namespace App\Http\Controllers\API;

use App\Models\Store;
use Exception;
use App\Casts\Image;
use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Http\Request;

class StoreController extends Controller {

    private $sanitize_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->logger_service = $logger_service;
    }

    public function show($store_id, Request $request){

        $store_id = $this->sanitize_service->sanitizeField($store_id);

        $this->logger_service->log('store.show', $request);

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

        return response()->json(['data' => $store]);
    }

}
