<?php

namespace App\Http\Controllers\API;

use App\Store;
use App\Traits\StoreTrait;
use Exception;
use App\Casts\Image;
use App\Http\Controllers\Controller;

class StoreController extends Controller
{
    
    use StoreTrait;

    /**
     * Show details about store:
     *  1. Name, Logo, Address
     *  2. Address
     *  3. Facilities
     *
     */
    public function show($store_id)
    {
        $store = Store::select('stores.*', 'store_types.large_logo', 'store_types.small_logo')
        ->where('stores.id', $store_id)
        ->join('store_types', 'store_types.id', '=', 'stores.store_type_id')
        ->withCasts([
            'large_logo' => Image::class,
            'small_logo' => Image::class,
        ])
        ->first();

        if($store){
            $store->location = $store->location;
            $store->opening_hours = $store->opening_hours;
            $store->facilities = $store->facilities;
        } else {
           throw new Exception('Store Not Found With ID: '. $store_id);
        }

        return $store;
    }

}
