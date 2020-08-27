<?php

namespace App\Http\Controllers;

use App\Facility;
use App\OpeningHour;
use App\Store;
use App\StoreLocation;
use App\Traits\StoreTrait;
use Illuminate\Http\Request;

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
        $store = Store::select('stores.*', 'store_types.large_logo', 'store_types.small_logo')->where('stores.id', $store_id)->join('store_types', 'store_types.id', '=', 'stores.store_type_id')->first();

        if($store){
            $store->location = $store->location;
            $store->opening_hours = $store->opening_hours;
            $store->facilities = $store->facilities;
        } else {
            $store = [];
        }

        return $store;
    }

}
